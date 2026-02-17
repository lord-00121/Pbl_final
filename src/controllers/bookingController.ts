import { Request, Response } from 'express';
import { BookingService } from '../services/bookingService';

export class BookingController {
    private bookingService: BookingService;

    constructor() {
        this.bookingService = new BookingService();
    }

    async checkAvailability(req: Request, res: Response) {
        const { venueId, date, time } = req.body;
        try {
            const availableSlots = await this.bookingService.checkAvailability(venueId, date, time);
            res.status(200).json({ availableSlots });
        } catch (error) {
            res.status(500).json({ message: 'Error checking availability', error });
        }
    }

    async createBooking(req: Request, res: Response) {
        const { userId, venueId, bookingTime, duration } = req.body;
        try {
            const booking = await this.bookingService.createBooking(userId, venueId, bookingTime, duration);
            res.status(201).json({ booking });
        } catch (error) {
            res.status(500).json({ message: 'Error creating booking', error });
        }
    }

    async getBookings(req: Request, res: Response) {
        const { userId } = req.params;
        try {
            const bookings = await this.bookingService.getBookingsByUserId(userId);
            res.status(200).json({ bookings });
        } catch (error) {
            res.status(500).json({ message: 'Error retrieving bookings', error });
        }
    }
}