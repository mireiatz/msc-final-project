from ml.preprocessing.preprocessing_pipeline import PreprocessingPipeline
from ml.preprocessing.prediction_data_ingestion_layer import PredictionDataIngestionLayer
from ml.preprocessing.cleaning_layer import CleaningLayer
from ml.preprocessing.feature_engineering_layer import FeatureEngineeringLayer
from ml.preprocessing.time_series_engineering_layer import TimeSeriesEngineeringLayer
from ml.config import config
import logging

class PredictionDataPreprocessingPipeline(PreprocessingPipeline):

    def __init__(self, historical_data, prediction_dates, output_path=None):
        super().__init__(output_path=output_path)
        self.historical_data = historical_data
        self.prediction_dates = prediction_dates

    def ingest_data(self):
        return PredictionDataIngestionLayer(historical_data=self.historical_data, prediction_dates=self.prediction_dates).process()

    def clean_data(self, ingested_data):
        return CleaningLayer(ingested_data).process_prediction_data()

    def engineer_features(self, cleaned_data):
        return FeatureEngineeringLayer(cleaned_data).process_prediction_data()

    def engineer_time_series(self, engineered_data):
        return TimeSeriesEngineeringLayer(engineered_data).process_prediction_data()

    def handle_data(self, preprocessed_data):
        logging.info("Handling prediction data...")

        # Drop any columns that are not in the required features list, keep ones necessary for identifying predictions
        required_columns = config.MAIN_FEATURES + ['source_product_id', 'date']
        prediction_data = preprocessed_data[required_columns]

        return prediction_data