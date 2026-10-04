#!/usr/bin/env python3
"""Audit every archived file; copy only DB-referenced attachments, never settings/.env.
Run as root; default read-only. Collision, unsafe path, link, unexpected disk/type fail closed.
"""
import argparse
import hashlib
import json
import os
from pathlib import Path, PurePosixPath
import pwd
import grp
import subprocess
import tarfile


def safe_path(name):
    path = PurePosixPath(name)
    if path.is_absolute() or '..' in path.parts or '\\' in name:
        raise ValueError('Unsafe archive path')
    return path


def audit(archive, destination, reference, apply=False):
    if not reference.startswith('g7_') or 'reference' not in reference or not reference.replace('_', '').isalnum():
        raise ValueError('Separate reference database required')
    records = []
    for table, module in [('board_attachments', 'sirsoft-board'), ('page_attachments', 'sirsoft-page')]:
        query = f"SELECT JSON_OBJECT('disk',disk,'path',path,'size',size,'mime',mime_type) FROM `{reference}`.g7_{table}"
        result = subprocess.run(['mariadb', '-N', '-B', '--raw', '-e', query], check=True, capture_output=True, text=True)
        for line in result.stdout.splitlines():
            record = json.loads(line)
            if record['disk'] != 'modules':
                raise ValueError('Unexpected attachment disk')
            safe_path(record['path'])
            record['relative'] = f"modules/{module}/attachments/{record['path']}"
            records.append(record)
    wanted = {f"storage/app/{r['relative']}": r for r in records}
    inventory = []
    copies = []
    with tarfile.open(archive, 'r:gz') as tar:
        seen = set()
        for member in tar:
            path = safe_path(member.name)
            if member.issym() or member.islnk() or (not member.isfile() and not member.isdir()):
                raise ValueError('Archive link/special entry rejected')
            if not member.isfile():
                continue
            if member.name in seen:
                raise ValueError('Duplicate archive member')
            seen.add(member.name)
            data = tar.extractfile(member).read()
            if str(path) == '.env':
                inventory.append({'path': '.env', 'decision': 'preserve_aws_secret_boundary'})
                continue
            if not str(path).startswith('storage/app/'):
                raise ValueError('Unexpected archive member')
            relative = str(path)[len('storage/app/'):]
            target = destination / relative
            # Reject symlinks through any existing ancestor (also for non-copied audit files).
            for parent in [target, *target.parents]:
                if parent == destination.parent:
                    break
                if parent.is_symlink():
                    raise ValueError('Target symlink rejected')
            identical = target.is_file() and hashlib.sha256(target.read_bytes()).digest() == hashlib.sha256(data).digest()
            decision = 'preserve_target_unreferenced'
            if member.name in wanted:
                record = wanted[member.name]
                if len(data) != record['size']:
                    raise ValueError('Attachment size mismatch')
                # The verified backup contains one text attachment on a deleted post.
                # Deliberately narrow allowlist; extend only after policy/MIME review.
                if not (target.suffix == '.txt' and record['mime'] == 'text/plain' and b'\x00' not in data):
                    raise ValueError('Attachment type requires review')
                if target.exists() and not identical:
                    raise ValueError('Attachment collision; never overwrite')
                decision = 'already_identical' if identical else 'restore_referenced_attachment'
                copies.append((target, data, identical))
            inventory.append({'path': str(path), 'target_exists': target.exists(), 'identical': identical, 'decision': decision})
        if set(wanted) - seen:
            raise ValueError('Referenced attachment absent from archive')
    # All validation completes before any writes; exclusive files and unchanged parent modes.
    if apply:
        uid, gid = pwd.getpwnam('ubuntu').pw_uid, grp.getgrnam('www-data').gr_gid
        for target, data, identical in copies:
            if identical:
                continue
            missing = []
            current = target.parent
            while not current.exists():
                missing.append(current)
                current = current.parent
            for directory in reversed(missing):
                directory.mkdir(mode=0o750)
                os.chown(directory, uid, gid)
            with target.open('xb') as output:
                output.write(data)
                output.flush()
                os.fsync(output.fileno())
            os.chmod(target, 0o640)
            os.chown(target, uid, gid)
    return {'applied': apply, 'archive_regular_files': len(inventory), 'referenced_files': len(records),
            'copied_files': sum(not same for _, _, same in copies) if apply else 0, 'files': inventory}


if __name__ == '__main__':
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('archive', type=Path)
    parser.add_argument('destination', type=Path)
    parser.add_argument('reference')
    parser.add_argument('--apply', action='store_true')
    args = parser.parse_args()
    print(json.dumps(audit(args.archive, args.destination, args.reference, args.apply), indent=2))
