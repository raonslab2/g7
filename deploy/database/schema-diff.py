#!/usr/bin/env python3
"""Read-only information_schema comparison; no row data or credentials emitted.
Run with socket-authenticated mariadb (e.g. sudo python3 this-file ...).
Raw metadata differences are evidence, not a production migration plan.
"""
import argparse
import json
import re
import subprocess

FIELDS = {
    "tables": ("TABLES", ["TABLE_NAME"], ["ENGINE", "TABLE_COLLATION", "TABLE_COMMENT"]),
    "columns": ("COLUMNS", ["TABLE_NAME", "COLUMN_NAME"], ["ORDINAL_POSITION", "COLUMN_TYPE", "IS_NULLABLE", "COLUMN_DEFAULT", "EXTRA", "CHARACTER_SET_NAME", "COLLATION_NAME", "COLUMN_COMMENT", "GENERATION_EXPRESSION"]),
    "indexes": ("STATISTICS", ["TABLE_NAME", "INDEX_NAME", "SEQ_IN_INDEX"], ["NON_UNIQUE", "COLUMN_NAME", "COLLATION", "SUB_PART", "INDEX_TYPE", "INDEX_COMMENT"]),
    "foreign_keys": ("KEY_COLUMN_USAGE", ["TABLE_NAME", "CONSTRAINT_NAME", "ORDINAL_POSITION"], ["COLUMN_NAME", "REFERENCED_TABLE_NAME", "REFERENCED_COLUMN_NAME", "POSITION_IN_UNIQUE_CONSTRAINT"]),
    "foreign_key_rules": ("REFERENTIAL_CONSTRAINTS", ["TABLE_NAME", "CONSTRAINT_NAME"], ["UPDATE_RULE", "DELETE_RULE", "UNIQUE_CONSTRAINT_NAME"]),
    "checks": ("CHECK_CONSTRAINTS", ["TABLE_NAME", "CONSTRAINT_NAME"], ["CHECK_CLAUSE"]),
}

def metadata(schema):
    result = {}
    for kind, (table, keys, values) in FIELDS.items():
        fields = keys + values
        obj = ",".join(f"'{f}',{f}" for f in fields)
        schema_field = "CONSTRAINT_SCHEMA" if kind in ("foreign_key_rules", "checks") else "TABLE_SCHEMA"
        where = f"{schema_field}='{schema}'"
        if kind == "foreign_keys":
            where += " AND REFERENCED_TABLE_NAME IS NOT NULL"
        query = f"SELECT JSON_OBJECT({obj}) FROM information_schema.{table} WHERE {where}"
        completed = subprocess.run(["mariadb", "--batch", "--skip-column-names", "--raw", "-e", query], check=True, text=True, capture_output=True)
        rows = [json.loads(line) for line in completed.stdout.splitlines()]
        result[kind] = {"/".join(str(row[k]) for k in keys): {v: row[v] for v in values} for row in rows}
    if not result["tables"]:
        raise ValueError("schema is empty or inaccessible")
    return result

if __name__ == "__main__":
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("production")
    parser.add_argument("reference")
    args = parser.parse_args()
    if args.production == args.reference or not all(re.fullmatch(r"[A-Za-z0-9_]+", x) for x in (args.production, args.reference)):
        parser.error("two distinct simple schema identifiers required")
    prod, ref = metadata(args.production), metadata(args.reference)
    report = {"production": args.production, "reference": args.reference, "table_counts": {"production": len(prod["tables"]), "reference": len(ref["tables"])}, "differences": {}}
    for kind in FIELDS:
        a, b = prod[kind], ref[kind]
        report["differences"][kind] = {
            "missing_in_production": {k: b[k] for k in sorted(b.keys() - a.keys())},
            "extra_in_production": {k: a[k] for k in sorted(a.keys() - b.keys())},
            "changed": {k: {"production": a[k], "reference": b[k]} for k in sorted(a.keys() & b.keys()) if a[k] != b[k]},
        }
    print(json.dumps(report, ensure_ascii=False, indent=2))
