# Service Quality Panel

**Web form to measure the quality of service provided by cashiers at SuperGIROS points of sale.**  
This tool improves decision-making regarding staff and helps identify the most common problems to enhance the service.

---

## Main Features

- **Login and roles:**  
  Access via authentication, with differentiated roles to view and manage information. Only authorized users can access administrative functions.

- **Session and inactivity control:**  
  For security, the system automatically logs out users after 3 minutes of inactivity.

- **User management:**  
  System to create and manage users with different permissions.

- **PostgreSQL database connection:**  
  Uses PDO for a secure and efficient connection to the database.

- **Advanced filters and searches:**  
  - Search by seller code.
  - Filter by rating average ranges.

- **Flexible sorting:**  
  Allows sorting results by average rating, either ascending or descending.

- **Modern interface:**  
  Designed with Bootstrap, it includes:
  - Sidebar with filters.
  - Results table showing: seller code, frequency, ratings, comments, name, phone number, and date.

- **Export to Excel:**  
  Allows downloading results easily for external analysis.

- **Security:**  
  Only users with the appropriate role can access and manage the information.

---

## Overview

This admin panel allows you to consult, filter, and analyze the results of SuperGIROS customer satisfaction surveys. It facilitates personnel management, identification of areas for improvement, and data-driven decision-making.

---

## Technologies Used

- PHP (PDO for PostgreSQL)
- Bootstrap (modern and responsive interface)
- JavaScript (session and inactivity management)
- Excel (data export)

---

## Quick Installation

1. Clone the repository.
2. Configure the PostgreSQL database connection in the configuration file.
3. Install the necessary dependencies.
4. Access the system through your browser.

---

## License

Internal use for SuperGIROS.
