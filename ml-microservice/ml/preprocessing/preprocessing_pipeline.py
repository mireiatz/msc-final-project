from ml.preprocessing.ingestion_layer import IngestionLayer
from ml.preprocessing.cleaning_layer import CleaningLayer
from ml.preprocessing.feature_engineering_layer import FeatureEngineeringLayer
from ml.config import config
import logging
import os

class PreprocessingPipeline:
    def __init__(self, data_path=None, output_path=None):
        self.data_path = data_path or config.HISTORICAL_DATA_RAW
        self.output_path = output_path or config.HISTORICAL_DATA_PROCESSED

    def run(self):
        # Step 1: Ingestion
        ingested_data = IngestionLayer(self.data_path).process()

        # Step 2: Call the specific cleaning and feature engineering processes
        cleaned_data = self.clean_data(ingested_data)
        structured_data = self.engineer_features(cleaned_data)

        # Step 3: Save the preprocessed data
        os.makedirs(self.output_path, exist_ok=True)

        structured_data.to_csv(self.output_path + '/processed_data.csv', index=False)

        logging.info("Preprocessing pipeline completed")

        return structured_data

    def clean_data(self, ingested_data):
        raise NotImplementedError("Subclass must implement 'clean_data'")

    def engineer_features(self, cleaned_data):
        raise NotImplementedError("Subclass must implement 'engineer_features'")
