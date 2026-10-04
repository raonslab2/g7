import importlib.util
import io
from pathlib import Path
import tarfile
import tempfile
import unittest
from unittest.mock import patch

spec = importlib.util.spec_from_file_location('restore_files', Path(__file__).parents[3] / 'deploy/database/restore-referenced-files.py')
restore = importlib.util.module_from_spec(spec)
spec.loader.exec_module(restore)


class RestoreFileBoundaryTest(unittest.TestCase):
    def test_absolute_and_parent_paths_are_rejected(self):
        for path in ['/etc/runtime.env', '../runtime.env', 'storage/app/../../.env', 'storage\\app\\x']:
            with self.subTest(path=path), self.assertRaises(ValueError):
                restore.safe_path(path)

    def test_environment_is_audited_but_never_copied(self):
        with tempfile.TemporaryDirectory() as directory:
            archive = Path(directory) / 'archive.tar.gz'
            destination = Path(directory) / 'target'
            destination.mkdir()
            with tarfile.open(archive, 'w:gz') as tar:
                data = b'original-environment-fixture'
                item = tarfile.TarInfo('.env'); item.size = len(data)
                tar.addfile(item, io.BytesIO(data))
            with patch.object(restore.subprocess, 'run') as run:
                run.return_value.stdout = ''
                result = restore.audit(archive, destination, 'g7_source_reference', True)
            self.assertFalse((destination / '.env').exists())
            self.assertEqual(0, result['copied_files'])
            self.assertEqual('preserve_aws_secret_boundary', result['files'][0]['decision'])

    def test_symlink_is_rejected_before_writes(self):
        with tempfile.TemporaryDirectory() as directory:
            archive = Path(directory) / 'archive.tar.gz'
            with tarfile.open(archive, 'w:gz') as tar:
                item = tarfile.TarInfo('storage/app/link'); item.type = tarfile.SYMTYPE; item.linkname = '/etc'
                tar.addfile(item)
            with patch.object(restore.subprocess, 'run') as run:
                run.return_value.stdout = ''
                with self.assertRaises(ValueError):
                    restore.audit(archive, Path(directory) / 'target', 'g7_source_reference', False)


if __name__ == '__main__':
    unittest.main()
