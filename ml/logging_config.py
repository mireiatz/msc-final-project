import logging

def setup_logging():
    logging.basicConfig(
        level=logging.INFO,  # Minimum logging level
        format='%(asctime)s - %(levelname)s - %(message)s',  # Log format
        handlers=[
            logging.FileHandler("app.log"),  # Log to file
            logging.StreamHandler()  # Log to console
        ],
    )
