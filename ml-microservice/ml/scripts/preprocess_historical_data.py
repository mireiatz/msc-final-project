import sys
import os
from ml.preprocessing.historical_data_preprocessing_pipeline import HistoricalDataPreprocessingPipeline

import argparse

def main():
    parser = argparse.ArgumentParser(description='Run preprocessing pipeline')
    parser.add_argument('--data_path', required=True, help='Path to the data (file or directory)')
    parser.add_argument('--output_path', required=False, help='Path to store the preprocessed data')
    parser.add_argument('--data_type', required=True, choices=['weekly', 'daily'], help='Type of data (weekly or daily)')

    args = parser.parse_args()

    # Run the appropriate preprocessing pipeline
    HistoricalDataPreprocessingPipeline(
        data_path=args.data_path,
        output_path=args.output_path,
        data_type=args.data_type,
    ).run()

if __name__ == "__main__":
    main()