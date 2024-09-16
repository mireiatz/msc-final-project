from preprocessing.preprocessing_pipeline import PreprocessingPipeline
import pandas as pd

# Define the input data directory and output path
data_directory = './ml/data/raw/'
output_path = './ml/data/scaled'

# Initialise and run the data pipeline
pipeline = PreprocessingPipeline(data_dir=data_directory, output_path=output_path)
final_data = pipeline.run()
