from ..logging_config import setup_logging
from ..preprocessing.preprocessing_pipeline import PreprocessingPipeline

setup_logging()

def run():
    # Run the preprocessing pipeline
    data_path = './ml/data/historical/raw_aug_21_23'
    output_path = './ml/data/historical/processed/processed_aug_21_23'
    pipeline = PreprocessingPipeline(
        data_path=data_path,
        output_path=output_path,
        data_type='weekly'
    )
    final_data = pipeline.run()

if __name__ == "__main__":
    run()