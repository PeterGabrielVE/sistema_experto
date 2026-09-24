"""Reads doctor-confirmed diagnoses from the Laravel database."""

from __future__ import annotations

import os

import pymysql

QUERY = """
    SELECT d.weight, d.size, d.age, d.physical_activity, p.gender, d.id_rule AS rule_id
    FROM diagnoses d
    JOIN patients p ON p.id = d.id_patient
    WHERE d.inference_source = 'manual'
      AND d.id_rule IS NOT NULL
      AND d.weight > 0 AND d.size > 0
"""


def manual_diagnoses() -> list[dict]:
    connection = pymysql.connect(
        host=os.environ.get("DB_HOST", "db"),
        port=int(os.environ.get("DB_PORT", "3306")),
        user=os.environ["DB_USERNAME"],
        password=os.environ.get("DB_PASSWORD", ""),
        database=os.environ["DB_DATABASE"],
        cursorclass=pymysql.cursors.DictCursor,
        connect_timeout=5,
    )
    try:
        with connection.cursor() as cursor:
            cursor.execute(QUERY)
            return list(cursor.fetchall())
    finally:
        connection.close()
