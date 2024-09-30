from ml.preprocessing.historical_data_preprocessing_pipeline import HistoricalDataPreprocessingPipeline
import os


def run():
    # Run the preprocessing pipeline
    pipeline = HistoricalDataPreprocessingPipeline(
        data_path = os.getenv('HISTORICAL_DATA_RAW'),
        output_path = os.getenv('HISTORICAL_DATA_PROCESSED'),
        data_type='daily'
    )
    final_data = pipeline.run()

if __name__ == "__main__":
    run()