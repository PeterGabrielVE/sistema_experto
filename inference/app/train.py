"""CLI: python -m app.train [--from-db]

Without --from-db it trains on synthetic data only (used when building the image).
"""

import argparse
import json

from . import data, model


def main() -> None:
    parser = argparse.ArgumentParser()
    parser.add_argument("--from-db", action="store_true", help="include doctor-confirmed diagnoses")
    args = parser.parse_args()

    manual = data.manual_diagnoses() if args.from_db else []
    trained = model.train(manual)
    model.save(trained)
    print(json.dumps(trained.metadata(), indent=2))


if __name__ == "__main__":
    main()
