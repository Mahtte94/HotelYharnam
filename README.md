# HotelYharnam

School project where we were supposed to build a mini hotel site using HTML,CSS,PHP and Javascript.

These are the inputs for the database:

CREATE TABLE Customers ( id INTEGER PRIMARY KEY autoincrement, transferCode VARCHAR(255) NOT NULL );

CREATE TABLE Bookings ( id INTEGER PRIMARY KEY autoincrement, arrival DATE NOT NULL, departure DATE NOT NULL, customerId INT NOT NULL, FOREIGN KEY (customerId) REFERENCES Customers(id) );

CREATE TABLE Rooms ( id INTEGER PRIMARY KEY autoincrement, name VARCHAR(255) NOT NULL, price DECIMAL(10, 2) NOT NULL, description TEXT);

CREATE TABLE Features ( id INTEGER PRIMARY KEY autoincrement, name VARCHAR(255) NOT NULL, price DECIMAL(10, 2) NOT NULL );

CREATE TABLE Booking_Rooms ( id INTEGER PRIMARY KEY autoincrement, bookingId INT NOT NULL, roomId INT NOT NULL, FOREIGN KEY (bookingId) REFERENCES Bookings(id), FOREIGN KEY (roomId) REFERENCES Rooms(id) );

CREATE TABLE Booking_Features ( id INTEGER PRIMARY KEY autoincrement, bookingId INT NOT NULL, featureId INT NOT NULL,  FOREIGN KEY (bookingId) REFERENCES Bookings(id), FOREIGN KEY (featureId) REFERENCES Features(id) );



insert into Rooms (name, price, description
) values ("economy", 1, "Bare essentials and creeping dread. Survival is not guaranteed, but the key is yours."), ("standard", 2, "Modest comfort with a touch of unease. The walls may whisper, but rest is possible—if you’re brave enough."), ("luxury", 4, "Lavish and perilous. Shadows covet your comfort as much as your sanity.");


insert into Features (name, price) values ("Guns", 1), ("Rifle", 2), ("Sauna", 2), ("Waterboiler", 4), ("Yatzy", 1);


Comments:

In the customers table in your database, a guest name column might be useful in order to connect the actual customer rather than a customer id to bookings

database.php handles many different responsibilities. Consider splitting it up.

booking.php:5-25 - consider implementing error handling

functions.php:13-44 - consider implementing error handling

Room prices are set both in index.php:111-113 and functions.php:82-86

There are many hard-coded values in your code. It might be better to move them to a config file for ease of maintenance and modification

Adding visual user feedback if something goes wrong might improve UX



