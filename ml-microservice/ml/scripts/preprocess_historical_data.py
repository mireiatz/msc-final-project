from ml.preprocessing.historical_data_preprocessing_pipeline import HistoricalDataPreprocessingPipeline
import os


def run():
    # Run the preprocessing pipeline

#     # Define paths to the shared directory for input and output
#     data_path = root_shared_path = os.getenv('SHARED') + '/sales_exports'
#     output_path = os.getenv('SHARED') + '/processed_data'
#     data_path = './ml/data/historical/new'
#     output_path = './ml/data/historical/processed/new'

    # Create output directory if it doesn't exist
#     if not os.path.exists(output_path):
#         os.makedirs(output_path)

    # Run the preprocessing pipeline
    pipeline = HistoricalDataPreprocessingPipeline(
        data_path = './ml/data/evaluation/raw',
        output_path = './ml/data/evaluation/processed',
        data_type='weekly'
    )
    final_data = pipeline.run()


if __name__ == "__main__":
    run()