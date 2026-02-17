import { Venue, IVenue } from '../models/venue';
import { Booking } from '../models/booking';

export class VenueService {
    async createVenue(venueData: any): Promise<IVenue> {
        const venue = new Venue(venueData);
        return await venue.save();
    }

    async updateVenue(venueId: string, venueData: any): Promise<IVenue | null> {
        return await Venue.findByIdAndUpdate(venueId, venueData, { new: true });
    }

    async getVenueById(venueId: string): Promise<IVenue | null> {
        return await Venue.findById(venueId);
    }

    async getAllVenues(): Promise<IVenue[]> {
        return await Venue.find();
    }

    async deleteVenue(venueId: string): Promise<IVenue | null> {
        return await Venue.findByIdAndDelete(venueId);
    }

    async checkAvailability(venueId: string, date: Date, duration: number): Promise<boolean> {
        const bookings = await Booking.find({ venueId, bookingTime: { $gte: date, $lt: new Date(date.getTime() + duration * 60 * 1000) } });
        return bookings.length === 0;
    }

    async uploadImages(venueId: string, images: any): Promise<IVenue | null> {
        return await Venue.findByIdAndUpdate(venueId, { images }, { new: true });
    }

    async manageAmenities(venueId: string, amenities: string[]): Promise<IVenue | null> {
        return await Venue.findByIdAndUpdate(venueId, { amenities }, { new: true });
    }

    async setPricing(venueId: string, pricing: number): Promise<IVenue | null> {
        return await Venue.findByIdAndUpdate(venueId, { pricing }, { new: true });
    }
}