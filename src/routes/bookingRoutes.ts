import { Router } from 'express';
import { BookingController } from '../controllers/bookingController';

const router = Router();
const bookingController = new BookingController();

// Route to check available slots
router.post('/check-availability', bookingController.checkAvailability);

// Route to create a booking
router.post('/create', bookingController.createBooking);

// Route to get bookings for a user
router.get('/user/:userId', bookingController.getBookings);

export default router;