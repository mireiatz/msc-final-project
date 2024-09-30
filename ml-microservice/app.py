from ml.logging_config import setup_logging
import ml.scripts.preprocess_historical_data as preprocess_historical_data
import logging
from ml.config import config

setup_logging()

logging.info("ML container running")

# preprocess_historical_data.run()