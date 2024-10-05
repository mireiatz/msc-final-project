import unittest
from unittest.mock import patch
import pandas as pd
from ml.preprocessing.file_ingestion_layer import FileIngestionLayer

class TestFileIngestionLayer(unittest.TestCase):

    @patch('pandas.read_csv')
    def test_read_file_success(self, mock_read_csv):
        """
        Test that a CSV file is successfully read into a DataFrame.
        """
        # Mock a return for read_csv
        mock_df = pd.DataFrame({'product_id': ['1', '2'], 'sales': [100, 200]})
        mock_read_csv.return_value = mock_df

        # Initialise layer and call read_file
        ingestion_layer = FileIngestionLayer(data_path='/dummy/path')
        df = ingestion_layer.read_file('/dummy/path/file.csv')

        # Check return matches mock
        pd.testing.assert_frame_equal(df, mock_df)

    @patch('pandas.read_csv', side_effect=pd.errors.EmptyDataError)
    def test_read_file_empty_error(self, mock_read_csv):
        """
        Test that an EmptyDataError is raised when a file is empty.
        """
        # Initialise layer
        ingestion_layer = FileIngestionLayer(data_path='/dummy/path')

        # Check that an EmptyDataError is raised
        with self.assertRaises(pd.errors.EmptyDataError):
            ingestion_layer.read_file('/dummy/path/file.csv')

    @patch('os.path.getsize', return_value=100)
    @patch('pandas.read_csv')
    def test_combine_files_success(self, mock_read_csv, mock_getsize):
        """
        Test that multiple CSV files are combined into a single DataFrame.
        """
        # Mock two DataFrames to be returned by read_csv for two files
        mock_df1 = pd.DataFrame({'product_id': ['1', '2'], 'sales': [100, 200]})
        mock_df2 = pd.DataFrame({'product_id': ['3', '4'], 'sales': [300, 400]})
        mock_read_csv.side_effect = [mock_df1, mock_df2]

        # Initialise the layer and call combine_files
        ingestion_layer = FileIngestionLayer(data_path='/dummy/path')
        files = ['/dummy/path/file1.csv', '/dummy/path/file2.csv']
        combined_df = ingestion_layer.combine_files(files)

        # Check that they have been combined and match the expected DataFrame
        expected_df = pd.concat([mock_df1, mock_df2], ignore_index=True)
        pd.testing.assert_frame_equal(combined_df, expected_df)

    @patch('os.path.exists', return_value=True)
    @patch('os.path.isfile', return_value=True)
    @patch('pandas.read_csv')
    def test_load_files_with_single_file(self, mock_read_csv, mock_isfile, mock_exists):
        """
        Test that load_files handles a single file correctly.
        """
        # Mock the return of read_csv
        mock_df = pd.DataFrame({'product_id': ['1', '2'], 'sales': [100, 200]})
        mock_read_csv.return_value = mock_df

        # Initialise the layer and call load_files
        ingestion_layer = FileIngestionLayer(data_path='/dummy/path/file.csv')
        df = ingestion_layer.load_files()

        # Check the loaded DataFrame
        pd.testing.assert_frame_equal(df, mock_df)

    @patch('os.path.isdir', return_value=True)
    @patch('os.path.getsize', return_value=100)
    @patch('os.path.isfile', return_value=False)  # Directory check should return False for file
    @patch('os.path.exists', return_value=True)
    @patch('os.path.join', side_effect=lambda *args: '/'.join(args))
    @patch('os.listdir', return_value=['file1.csv', 'file2.csv'])
    @patch('pandas.read_csv')
    def test_load_files_with_multiple_files(self, mock_read_csv, mock_listdir, mock_join, mock_exists, mock_isfile, mock_getsize, mock_isdir):
        """
        Test that load_files handles a directory with multiple valid CSV files.
        """
        # Mock DataFrames returned by read_csv for two files in the directory
        mock_df1 = pd.DataFrame({'product_id': ['1', '2'], 'sales': [100, 200]})
        mock_df2 = pd.DataFrame({'product_id': ['3', '4'], 'sales': [300, 400]})
        mock_read_csv.side_effect = [mock_df1, mock_df2]

        # Initialise the layer and call load_files
        ingestion_layer = FileIngestionLayer(data_path='/dummy/path')
        df = ingestion_layer.load_files()

        # Check DataFrames are combined and match the expected DataFrame
        expected_df = pd.concat([mock_df1, mock_df2], ignore_index=True)
        pd.testing.assert_frame_equal(df, expected_df)

if __name__ == '__main__':
    unittest.main()
