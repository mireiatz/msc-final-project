from ml.preprocessing.preprocessing_pipeline import PreprocessingPipeline
from ml.preprocessing.file_ingestion_layer import FileIngestionLayer
from ml.preprocessing.cleaning_layer import CleaningLayer
from ml.preprocessing.general_feature_engineering_layer import GeneralFeatureEngineeringLayer
from ml.preprocessing.time_series_engineering_layer import TimeSeriesEngineeringLayer
from ml.config import config

class HistoricalDataPreprocessingPipeline(PreprocessingPipeline):

    def __init__(self, data_path=None, output_path=None, data_type='daily'):
        super().__init__(
            data_path or config.HISTORICAL_DATA_RAW,
            output_path or config.HISTORICAL_DATA_PROCESSED
        )
        self.data_type = data_type

    def ingest_data(self):
        return FileIngestionLayer(data_path=self.data_path).process()

    def clean_data(self, ingested_data):
        cleaning = CleaningLayer(ingested_data)
        if self.data_type == 'daily':
            return cleaning.process_historical_daily_data()
        elif self.data_type == 'weekly':
            return cleaning.process_historical_weekly_data()

    def engineer_data(self, cleaned_data):
        general_feature_engineering = GeneralFeatureEngineeringLayer(cleaned_data)

        if self.data_type == 'daily':
            engineered_data = general_feature_engineering.process_historical_daily_data()
        elif self.data_type == 'weekly':
            engineered_data = general_feature_engineering.process_historical_weekly_data()

        time_series_engineering = TimeSeriesEngineeringLayer(engineered_data)
        return time_series_engineering.process_historical_data()