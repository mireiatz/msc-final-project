# Use the official Python 3.12 image as the base image
FROM python:3.12

# Set the working directory inside the container
WORKDIR /usr/src/app

# Copy the requirements.txt file to the working directory and install dependencies
COPY requirements.txt ./
RUN pip install --no-cache-dir -r requirements.txt

# Copy all files from the local directory to the container's working directory
COPY . .

# Expose port 5000
EXPOSE 5002

# Run the Flask application
CMD ["python3", "app.py"]