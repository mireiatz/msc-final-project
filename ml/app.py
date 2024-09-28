from preprocessing.preprocessing_pipeline import PreprocessingPipeline
import pandas as pd

# Run the preprocessing pipeline
data_directory = './ml/data/training/raw/'
output_path = './ml/data/training/processed'
pipeline = PreprocessingPipeline(data_dir=data_directory, output_path=output_path)
final_data = pipeline.run()
