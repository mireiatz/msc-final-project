import os
from dotenv import load_dotenv

load_dotenv()

class Config:
    # Load environment variables
    SHARED = os.getenv('SHARED', '/shared')
    HISTORICAL_DATA_RAW = os.getenv('HISTORICAL_DATA_RAW', f'{SHARED}/historical_data/raw')
    HISTORICAL_DATA_PROCESSED = os.getenv('HISTORICAL_DATA_PROCESSED', f'{SHARED}/historical_data/processed')
    PREDICTION_DATA_RAW = os.getenv('PREDICTION_DATA_RAW', f'{SHARED}/prediction_data/raw')
    PREDICTION_DATA_PROCESSED = os.getenv('PREDICTION_DATA_PROCESSED', f'{SHARED}/prediction_data/processed')

    # Add configurations
    DEFAULT_DISK = 'local'
    STORAGE_DISKS = {
        'historical_data_raw': HISTORICAL_DATA_RAW,
        'historical_data_processed': HISTORICAL_DATA_PROCESSED,
        'prediction_data_raw': PREDICTION_DATA_RAW,
        'prediction_data_processed': PREDICTION_DATA_PROCESSED,
    }

config = Config()
