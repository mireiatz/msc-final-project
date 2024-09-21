import unittest
from unittest.mock import patch
import pandas as pd
import os
from ml.preprocessing.ingestion_layer import IngestionLayer

class TestIngestionLayer(unittest.TestCase):

    @patch('os.listdir')
    @patch('os.path.getsize')
    @patch('os.path.exists')
    @patch('os.path.join')
    @patch('pandas.read_csv')
    def test_load_and_combine_weekly_files(self, mock_read_csv, mock_path_join, mock_exists, mock_getsize, mock_listdir):
        """
        Test the the loading and combinations of data from multiple files.
        """
        # Mock the files in the directory
        mock_listdir.return_value = ['week1.csv', 'week2.csv']

        # Mock os.path.exists to return True (so the directory is "found")
        mock_exists.return_value = True

        # Mock the file size to be non-zero (so the files are considered non-empty)
        mock_getsize.return_value = 100

        # Mock os.path.join to return a dummy file path
        mock_path_join.side_effect = lambda *args: '/non/existent/path/' + args[-1]

        # Create sample dataframes for the mock CSV files and set expected dataframe
        mock_df1 = pd.DataFrame({'product_id': ['1', '2'], 'sales': [100, 200]})
        mock_df2 = pd.DataFrame({'product_id': ['3', '4'], 'sales': [300, 400]})
        expected_df = pd.concat([mock_df1, mock_df2], ignore_index=True)

        # Set the return values of pandas.read_csv when called for each file
        mock_read_csv.side_effect = [mock_df1, mock_df2]

        # Initialise IngestionLayer and test load_and_combine_weekly_files method
        ingestion_layer = IngestionLayer(data_dir='/dummy/path', output_path='/dummy/output.csv')
        combined_df = ingestion_layer.load_and_combine_weekly_files()

        # Assert the combined DataFrame is correct
        pd.testing.assert_frame_equal(combined_df, expected_df)

if __name__ == '__main__':
    unittest.main()
