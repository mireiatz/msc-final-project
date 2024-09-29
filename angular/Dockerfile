# Use the official Node.js 18 image as the base image
FROM node:18

# Set the working directory inside the container
WORKDIR /usr/src/app

# Copy package.json and package-lock.json files to the working directory
COPY package*.json ./

# Install the Node.js dependencies using npm
RUN npm install

# Copy all files from the local directory to the container's working directory
COPY . .

# Expose port 4200
EXPOSE 4200

# Run the application using npm start
CMD ["npm", "start"]
