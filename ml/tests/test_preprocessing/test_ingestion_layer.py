import unittest
from unittest.mock import patch
import pandas as pd
from ml.preprocessing.ingestion_layer import IngestionLayer

class TestIngestionLayer(unittest.TestCase):

    @patch('os.listdir')
    @patch('os.path.getsize')
    @patch('os.path.exists')
    @patch('os.makedirs')
    @patch('os.path.join')
    @patch('pandas.read_csv')
    def test_load_and_combine_files(self, mock_read_csv, mock_path_join, mock_makedirs, mock_exists, mock_getsize, mock_listdir):
        """
        Test loading and combining data from multiple non-empty files.
        """
        # Mock the files in the directory
        mock_listdir.return_value = ['week1.csv', 'week2.csv']

        # Mock os.path.exists to return True (so the directory is "found")
        mock_exists.side_effect = [False, True]  # First for output, second for data_path

        # Mock the file size to be non-zero (so the files are considered non-empty)
        mock_getsize.return_value = 100

        # Mock os.path.join to return a dummy file path
        mock_path_join.side_effect = lambda *args: '/non/existent/path/' + args[-1]

        # Create sample DataFrames for the mock CSV files and set expected DataFrame
        mock_df1 = pd.DataFrame({'product_id': ['1', '2'], 'sales': [100, 200]})
        mock_df2 = pd.DataFrame({'product_id': ['3', '4'], 'sales': [300, 400]})
        expected_df = pd.concat([mock_df1, mock_df2], ignore_index=True)

        # Set the return values of pandas.read_csv when called for each file
        mock_read_csv.side_effect = [mock_df1, mock_df2]

        # Initialise IngestionLayer and test 'load_and_combine_files' method
        ingestion_layer = IngestionLayer(data_path='/dummy/path', output_path='/dummy/output.csv')
        combined_df = ingestion_layer.load_and_combine_files()

        # Assert the combined DataFrame is correct
        pd.testing.assert_frame_equal(combined_df, expected_df)

    @patch('os.path.exists')
    @patch('pandas.read_csv')
    def test_process_with_cache(self, mock_read_csv, mock_exists):
        """
        Test the process function when cached output is already available.
        """
        # Mock the output file to exist
        mock_exists.return_value = True

        # Create a mock DataFrame to simulate cached data
        mock_df = pd.DataFrame({'product_id': ['1', '2'], 'sales': [100, 200]})

        # Mock 'read_csv' to return the cached DataFrame
        mock_read_csv.return_value = mock_df

        # Initialise IngestionLayer and test the process method
        ingestion_layer = IngestionLayer(data_path='/dummy/path', output_path='/dummy/output.csv')
        processed_df = ingestion_layer.process()

        # Assert the DataFrame loaded from cache is correct
        pd.testing.assert_frame_equal(processed_df, mock_df)

if __name__ == '__main__':
    unittest.main()
