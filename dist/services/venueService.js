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
exports.VenueService = void 0;
const venue_1 = require("../models/venue");
const booking_1 = require("../models/booking");
class VenueService {
    createVenue(venueData) {
        return __awaiter(this, void 0, void 0, function* () {
            const venue = new venue_1.Venue(venueData);
            return yield venue.save();
        });
    }
    updateVenue(venueId, venueData) {
        return __awaiter(this, void 0, void 0, function* () {
            return yield venue_1.Venue.findByIdAndUpdate(venueId, venueData, { new: true });
        });
    }
    getVenueById(venueId) {
        return __awaiter(this, void 0, void 0, function* () {
            return yield venue_1.Venue.findById(venueId);
        });
    }
    getAllVenues() {
        return __awaiter(this, void 0, void 0, function* () {
            return yield venue_1.Venue.find();
        });
    }
    deleteVenue(venueId) {
        return __awaiter(this, void 0, void 0, function* () {
            return yield venue_1.Venue.findByIdAndDelete(venueId);
        });
    }
    checkAvailability(venueId, date, duration) {
        return __awaiter(this, void 0, void 0, function* () {
            const bookings = yield booking_1.Booking.find({ venueId, bookingTime: { $gte: date, $lt: new Date(date.getTime() + duration * 60 * 1000) } });
            return bookings.length === 0;
        });
    }
    uploadImages(venueId, images) {
        return __awaiter(this, void 0, void 0, function* () {
            return yield venue_1.Venue.findByIdAndUpdate(venueId, { images }, { new: true });
        });
    }
    manageAmenities(venueId, amenities) {
        return __awaiter(this, void 0, void 0, function* () {
            return yield venue_1.Venue.findByIdAndUpdate(venueId, { amenities }, { new: true });
        });
    }
    setPricing(venueId, pricing) {
        return __awaiter(this, void 0, void 0, function* () {
            return yield venue_1.Venue.findByIdAndUpdate(venueId, { pricing }, { new: true });
        });
    }
}
exports.VenueService = VenueService;
