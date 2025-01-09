# HotelYharnam

School project where we were supposed to build a mini hotel site using HTML,CSS,PHP and Javascript.

These are the inputs for the database:

CREATE TABLE Customers (
    id INTEGER PRIMARY KEY autoincrement,
    transferCode VARCHAR(255) NOT NULL
);


CREATE TABLE Bookings (
    id INTEGER PRIMARY KEY autoincrement,
    arrival DATE NOT NULL,
    departure DATE NOT NULL,
    customerId INT NOT NULL,
    FOREIGN KEY (customerId) REFERENCES customers(id)
);


CREATE TABLE Rooms (
    id INTEGER PRIMARY KEY autoincrement,
    name VARCHAR(255) NOT NULL,
    price DECIMAL(10, 2) NOT NULL
);


CREATE TABLE Features (
    id INTEGER PRIMARY KEY autoincrement,
    name VARCHAR(255) NOT NULL,
    price DECIMAL(10, 2) NOT NULL
);


CREATE TABLE Booking_Rooms (
    id INTEGER PRIMARY KEY autoincrement,
    bookingId INT NOT NULL,
    roomId INT NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (bookingId) REFERENCES bookings(id),
    FOREIGN KEY (roomId) REFERENCES rooms(id)
);


CREATE TABLE Booking_Features (
    id INTEGER PRIMARY KEY autoincrement,
    bookingId INT NOT NULL,
    featureId INT NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (bookingId) REFERENCES bookings(id),
    FOREIGN KEY (featureId) REFERENCES features(id)
);




