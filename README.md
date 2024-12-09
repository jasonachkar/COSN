---

# COSN - Social Network Platform

## Group Information

Group 2

- Max Legault:
ID : 40173403
ENCS: ma_egaul@encs.concordia.ca
- Khaled Rezgui:
ID: 40176606 
ENCS: k_rezgui@encs.concordia.ca
- Jason Achkar Diab:
ID: 40227239 
ENCS: ja_achka@encs.concordia.ca
- Nadezhda Gagnon:
ID: 40277786
ENCS: g_nadezh@encs.concordia.ca

Group Account:
- gpc353_2@encs.concordia.ca

Project URL:
https://can01.safelinks.protection.outlook.com/?
 url=https%3A%2F%2Fgpc353.encs.concordia.ca%2F&data=05%7C02%7Cjason.achkardiab%40mail.co
 ncordia.ca%7C15bdb6c8a7874a93323f08dcde5c11dd%7C5569f185d22f4e139850ce5b1abcd2e8%7C0%
 7C0%7C638629734231325908%7CUnknown%7CTWFpbGZsb3d8eyJWIjoiMC4wLjAwMDAiLCJQIjoi
 V2luMzIiLCJBTiI6Ik1haWwiLCJXVCI6Mn0%3D%7C0%7C%7C%7C&sdata=T7qES2dUSFmI1fLNW
 05ZFp65%2FoDH9I0XzTD80NDTskM%3D&reserved=0

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
