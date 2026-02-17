import { Booking, IBooking } from '../models/booking';
import { Venue } from '../models/venue';

export class BookingService {
    constructor() {}

    public async checkAvailability(venueId: string, date: string, time: string): Promise<boolean> {
        const bookingTime = new Date(`${date}T${time}`);
        const existingBookings = await Booking.find({ 
            venueId, 
            bookingTime: { $gte: bookingTime, $lt: new Date(bookingTime.getTime() + 2 * 60 * 60 * 1000) } // 2 hours buffer
        });
        return existingBookings.length === 0;
    }

    public async createBooking(userId: string, venueId: string, bookingTime: string, duration: number): Promise<IBooking> {
        const venue = await Venue.findById(venueId);
        if (!venue) {
            throw new Error('Venue not found');
        }

        const isAvailable = await this.checkAvailability(venueId, bookingTime.split('T')[0], bookingTime.split('T')[1]);
        if (!isAvailable) {
            throw new Error('Venue is not available for the selected time.');
        }

        const totalAmount = venue.pricing * duration;
        const newBooking = new Booking({
            userId,
            venueId,
            bookingTime: new Date(bookingTime),
            duration,
            totalAmount,
            status: 'pending'
        });

        return await newBooking.save();
    }

    public async getBookingsByUserId(userId: string): Promise<IBooking[]> {
        return await Booking.find({ userId }).populate('venueId');
    }

    public async getBookingById(bookingId: string): Promise<IBooking | null> {
        return await Booking.findById(bookingId).populate('venueId');
    }

    public async updateBookingStatus(bookingId: string, status: 'pending' | 'confirmed' | 'cancelled' | 'completed'): Promise<IBooking | null> {
        return await Booking.findByIdAndUpdate(bookingId, { status }, { new: true });
    }
}