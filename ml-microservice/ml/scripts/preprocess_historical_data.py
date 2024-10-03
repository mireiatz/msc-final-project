from ml.preprocessing.historical_data_preprocessing_pipeline import HistoricalDataPreprocessingPipeline
import argparse

def main():
    logging.info("Starting historical preprocessing script...")

    # Get the arguments
    parser = argparse.ArgumentParser(description='Run historical preprocessing pipeline')
    parser.add_argument('--data_path', required=True, help='Path to the data (file or directory)')
    parser.add_argument('--output_path', required=False, help='Path to store the preprocessed data')
    parser.add_argument('--data_type', required=True, choices=['weekly', 'daily'], help='Type of data (weekly or daily)')

    args = parser.parse_args()

    # Run the pipeline
    HistoricalDataPreprocessingPipeline(
        data_path=args.data_path,
        output_path=args.output_path,
        data_type=args.data_type,
    ).run()

    logging.info("Historical preprocessing script completed")

if __name__ == "__main__":
    main()