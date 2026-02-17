"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
const express_1 = require("express");
const bookingController_1 = require("../controllers/bookingController");
const router = (0, express_1.Router)();
const bookingController = new bookingController_1.BookingController();
// Route to check available slots
router.post('/check-availability', bookingController.checkAvailability);
// Route to create a booking
router.post('/create', bookingController.createBooking);
// Route to get bookings for a user
router.get('/user/:userId', bookingController.getBookings);
exports.default = router;
