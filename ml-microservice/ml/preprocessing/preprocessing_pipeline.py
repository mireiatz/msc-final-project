from ml.config import config
import logging
import os
from datetime import datetime

class PreprocessingPipeline:

    def __init__(self, data_path=None, output_path=None):
        self.data_path = data_path
        self.output_path = output_path

    def run(self):
        logging.info("Starting preprocessing pipeline...")

        # Step 1: Ingest the data
        ingested_data = self.ingest_data()

        # Step 2: Call the specific cleaning process
        cleaned_data = self.clean_data(ingested_data)

        # Step 3: Engineer features as required
        engineered_data = self.engineer_features(cleaned_data)

        # Step 4: Engineer the time series
        time_series_data = self.engineer_time_series(engineered_data)

        # Step 5: Save the preprocessed data after making a backup
        return self.handle_data(time_series_data)

    def ingest_data(self):
        raise NotImplementedError("Subclass must implement 'ingest_data'")

    def clean_data(self, ingested_data):
        raise NotImplementedError("Subclass must implement 'clean_data'")

    def engineer_features(self, cleaned_data):
        raise NotImplementedError("Subclass must implement 'engineer_features'")

    def engineer_time_series(self, cleaned_data):
        raise NotImplementedError("Subclass must implement 'engineer_time_series'")

    def handle_data(self, preprocessed_data):
        """
        Basic handling of fully preprocessed data by returning it, override to handle otherwise.
        """
        return preprocessed_data
