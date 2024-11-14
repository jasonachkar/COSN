---

# COSN - Social Network Platform

## Project Overview

COSN (Community Online Social Network) is a web-based social networking platform designed to allow users to connect, communicate, and share events with each other. The platform includes features such as user registration and login, friend requests, friend suggestions, event creation, group messaging, and notifications, providing a familiar, Facebook-like experience.

## Features

- **User Authentication**: Secure registration and login system with password hashing.
- **Friend Management**: Add, accept, decline friend requests, and view friend suggestions.
- **Event Management**: Create and view upcoming events.
- **Group Messaging**: Communicate with friends in private or group chats.
- **Notifications**: View notifications for friend requests and event updates.

## Project Structure

### Main Pages

- `index.php`: Landing page with an overview and links to login and register.
- `login.php`: Login page for registered users.
- `register.php`: Registration page for new users.
- `home.php`: User dashboard, showing navigation options for messages, events, friends, and more.
- `friends.php`: Manage friends, view friend suggestions, and see pending friend requests.
- `events.php`: Create and view events, with details on title, description, location, date, and time.
- `groups.php`: Page for group chats and managing group memberships.
- `profile.php`: User profile page with options to update account settings.
- `messages.php`: Page to send and view messages in real time with friends or groups.

### Folder Structure

- `styles/`: Contains CSS files for styling the application.
- `js/`: Contains JavaScript files for front-end functionality.
- `php/`: Contains PHP files for backend logic and database interactions.

## Installation

### Prerequisites

- PHP (v8.0 or later recommended)
- MySQL database
- [XAMPP](https://www.apachefriends.org/index.html) (for an Apache server) or any other local server solution.
- Optional: [Five Server](https://marketplace.visualstudio.com/items?itemName=yandeu.five-server) extension for running PHP directly in VS Code.

### Setup Instructions

1. **Clone the Repository**

   ```bash
   git clone https://github.com/your-username/cosn.git
   cd cosn
   ```

2. **Database Setup**

   - Open **MySQL** and create a new database named `cosn`.
   - Import the SQL file (e.g., `cosn.sql`) in the `database` folder to set up tables and initial data.

3. **Database Configuration**

   - Open `database.php` and update the database connection details to match your environment.

   ```php
   <?php
   $servername = "localhost";
   $username = "root";
   $password = "your_password";
   $dbname = "cosn";
   $conn = new mysqli($servername, $username, $password, $dbname);
   if ($conn->connect_error) {
       die("Connection failed: " . $conn->connect_error);
   }
   ?>
   ```

4. **Start Apache Server**

   - Start Apache and MySQL via XAMPP or any other server tool.
   - Place the project files in the `htdocs` folder if using XAMPP.

5. **Access the Application**

   - Open a web browser and go to `http://localhost/cosn` to access the platform.

## Usage

### User Authentication

1. **Register**: Go to the registration page (`register.php`) and create an account.
2. **Login**: Use the login page (`login.php`) to access your dashboard.

### Friends

- Go to the **Friends** page to send friend requests, view pending requests, and accept/decline them.
- The **Friend Suggestions** section displays users who are not yet your friends.

### Events

- Visit the **Events** page to create new events and view upcoming events.
- Fill out the event form with title, description, location, date, and time, then click **Create Event**.

### Messaging

- Go to the **Messages** page to view and send messages to friends or within groups.
- The platform supports group and private messaging.

## Technologies Used

- **Frontend**: HTML, CSS, JavaScript
- **Backend**: PHP
- **Database**: MySQL

## Project Screenshots

Include screenshots of key pages like the login, home, friends, and events pages to showcase the UI and functionality.

## Known Issues

- Provide a list of known issues or bugs if there are any. For example:
  - *Friend suggestions sometimes show previously requested users.*
  - *UI inconsistencies on different screen sizes.*

## Contributing

If you want to contribute to COSN:

1. Fork the project.
2. Create a new branch for your feature (`git checkout -b feature-name`).
3. Commit your changes (`git commit -m 'Add some feature'`).
4. Push the branch (`git push origin feature-name`).
5. Open a Pull Request.

## License

MIT License. See `LICENSE` for more details.

---
