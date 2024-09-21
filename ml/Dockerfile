FROM python:3.9.6

WORKDIR /usr/src/app

COPY requirements.txt ./
RUN echo "Installing dependencies..."

RUN pip3 install --no-cache-dir -r requirements.txt

COPY . .

EXPOSE 5000
CMD ["python", "app.py"]
