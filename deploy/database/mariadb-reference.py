#!/usr/bin/env python3
"""Render reference-only MariaDB SQL. Never connects to a database."""
import argparse
import re
from pathlib import Path

parser = argparse.ArgumentParser(description=__doc__)
parser.add_argument("snapshot", type=Path)
parser.add_argument("output", type=Path)
args = parser.parse_args()
if args.snapshot.resolve() == args.output.resolve():
    parser.error("output must differ from the original snapshot")
sql = args.snapshot.read_text()
pattern = r"/\*!50100 WITH PARSER `ngram` \*/"
count = len(re.findall(pattern, sql))
if not count or len(re.findall(r"WITH PARSER", sql)) != count:
    parser.error("unexpected parser definitions; review the snapshot")
with args.output.open("x") as output:
    output.write("-- REFERENCE DB ONLY: MariaDB default FULLTEXT parser differs from MySQL ngram.\n")
    output.write(re.sub(pattern, "", sql))
print(f"Rendered {count} explicit parser substitutions; original unchanged.")
