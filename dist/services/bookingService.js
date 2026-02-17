"use strict";
var __awaiter = (this && this.__awaiter) || function (thisArg, _arguments, P, generator) {
    function adopt(value) { return value instanceof P ? value : new P(function (resolve) { resolve(value); }); }
    return new (P || (P = Promise))(function (resolve, reject) {
        function fulfilled(value) { try { step(generator.next(value)); } catch (e) { reject(e); } }
        function rejected(value) { try { step(generator["throw"](value)); } catch (e) { reject(e); } }
        function step(result) { result.done ? resolve(result.value) : adopt(result.value).then(fulfilled, rejected); }
        step((generator = generator.apply(thisArg, _arguments || [])).next());
    });
};
Object.defineProperty(exports, "__esModule", { value: true });
exports.BookingService = void 0;
const booking_1 = require("../models/booking");
const venue_1 = require("../models/venue");
class BookingService {
    constructor() { }
    checkAvailability(venueId, date, time) {
        return __awaiter(this, void 0, void 0, function* () {
            const bookingTime = new Date(`${date}T${time}`);
            const existingBookings = yield booking_1.Booking.find({
                venueId,
                bookingTime: { $gte: bookingTime, $lt: new Date(bookingTime.getTime() + 2 * 60 * 60 * 1000) } // 2 hours buffer
            });
            return existingBookings.length === 0;
        });
    }
    createBooking(userId, venueId, bookingTime, duration) {
        return __awaiter(this, void 0, void 0, function* () {
            const venue = yield venue_1.Venue.findById(venueId);
            if (!venue) {
                throw new Error('Venue not found');
            }
            const isAvailable = yield this.checkAvailability(venueId, bookingTime.split('T')[0], bookingTime.split('T')[1]);
            if (!isAvailable) {
                throw new Error('Venue is not available for the selected time.');
            }
            const totalAmount = venue.pricing * duration;
            const newBooking = new booking_1.Booking({
                userId,
                venueId,
                bookingTime: new Date(bookingTime),
                duration,
                totalAmount,
                status: 'pending'
            });
            return yield newBooking.save();
        });
    }
    getBookingsByUserId(userId) {
        return __awaiter(this, void 0, void 0, function* () {
            return yield booking_1.Booking.find({ userId }).populate('venueId');
        });
    }
    getBookingById(bookingId) {
        return __awaiter(this, void 0, void 0, function* () {
            return yield booking_1.Booking.findById(bookingId).populate('venueId');
        });
    }
    updateBookingStatus(bookingId, status) {
        return __awaiter(this, void 0, void 0, function* () {
            return yield booking_1.Booking.findByIdAndUpdate(bookingId, { status }, { new: true });
        });
    }
}
exports.BookingService = BookingService;
