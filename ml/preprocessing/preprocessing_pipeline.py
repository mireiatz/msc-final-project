from ml.preprocessing.ingestion_layer import IngestionLayer
from ml.preprocessing.cleaning_layer import CleaningLayer
from ml.preprocessing.feature_engineering_layer import FeatureEngineeringLayer
from datetime import datetime
from ml.logging_config import setup_logging
import logging
import time

class PreprocessingPipeline:

    def __init__(self, data_path, output_path):
        self.data_path = data_path
        self.output_path = output_path

    def run(self):
        """
        Run every step to preprocess data.
        """
        setup_logging()

        # Step 1: Ingestion - Read raw data from a directory/file and save to 'raw_data.csv'
        ingestion = IngestionLayer(self.data_path, self.output_path + '/raw_data.csv')
        ingested_data = ingestion.process()

        # Step 2: Cleaning - Clean the ingested data
        cleaning = CleaningLayer(ingested_data)
        cleaned_data = cleaning.process()

        # Step 3: Feature Engineering - Re-structure the dataset and create additional features
        feature_engineering = FeatureEngineeringLayer(cleaned_data)
        structured_data = feature_engineering.process()

        # Step 4: Save data
        logging.info("Saving preprocessed data...")
        structured_data.to_csv(self.output_path + '/processed_data.csv', index=False)

        logging.info(f"Data pipeline finished processing")

        return structured_data
