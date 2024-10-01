from ml.preprocessing.preprocessing_pipeline import PreprocessingPipeline
from ml.preprocessing.ingestion_layer import IngestionLayer
from ml.preprocessing.cleaning_layer import CleaningLayer
from ml.preprocessing.feature_engineering_layer import FeatureEngineeringLayer
from ml.config import config

class HistoricalDataPreprocessingPipeline(PreprocessingPipeline):

    def __init__(self, data_path=None, output_path=None, data_type='daily'):
        super().__init__(
            data_path or config.HISTORICAL_DATA_RAW,
            output_path or config.HISTORICAL_DATA_PROCESSED
        )
        self.data_type = data_type

    def clean_data(self, ingested_data):
        cleaning = CleaningLayer(ingested_data)
        if self.data_type == 'daily':
            return cleaning.process_historical_daily_data()
        elif self.data_type == 'weekly':
            return cleaning.process_historical_weekly_data()

    def engineer_features(self, cleaned_data):
        feature_engineering = FeatureEngineeringLayer(cleaned_data)
        if self.data_type == 'daily':
            return feature_engineering.process_historical_daily_data()
        elif self.data_type == 'weekly':
            return feature_engineering.process_historical_weekly_data()