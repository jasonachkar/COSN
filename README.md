

### Friends Page (`friends.php`)
1. **Layout Structure**:
   - We organized the page into three sections: **Your Friends**, **Pending Friend Requests**, and **Friend Suggestions**.
   - Placed an **Add a Friend** form at the top of the page where users can enter a username to send a friend request.
   - Arranged the three main sections (Friends, Pending Requests, and Suggestions) horizontally so they span the full width of the page.

2. **Dynamic Content**:
   - Implemented queries to fetch the user’s friends, pending friend requests, and suggested friends from the database.
   - Updated the friend suggestions to dynamically refresh after sending a friend request, ensuring the same user doesn’t reappear in the suggestions list.

3. **CSS Styling**:
   - Styled each section consistently, similar to the styling used in other parts of the project.
   - Ensured input fields, buttons, and layout had a clean, professional appearance.

4. **Back Button**:
   - Added a **Back** button in the header that redirects users to the homepage if logged in, or to the index page if not logged in.

### Events Page (`events.php`)
1. **Page Layout**:
   - Structured the **Create an Event** form on the left side with fields for event title, description, location, date, and time.
   - Positioned the **Upcoming Events** section on the right side, displaying future events.

2. **Form Structure**:
   - Placed event fields in the following order for clarity:
     - **Event Title** (Top)
     - **Description** (Under Title)
     - **Location** (Under Description)
     - **Date and Time** fields (next to each other)
   - Styled the **Create Event** button below these fields for a clear call to action.

3. **Upcoming Events Section**:
   - Dynamically displayed a list of upcoming events on the right side of the page.
   - Fetched and displayed all events from the database with dates and times in the future.

4. **Styling**:
   - Applied CSS styling to match the appearance of the friends page.
   - Styled the input fields and buttons for consistency across the project.

5. **Back Button in Header**:
   - Added a **Back** button to the header, allowing users to easily return to the homepage.

