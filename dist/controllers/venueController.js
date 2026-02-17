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
exports.VenueController = void 0;
const venueService_1 = require("../services/venueService");
class VenueController {
    constructor() {
        this.venueService = new venueService_1.VenueService();
    }
    createVenue(req, res) {
        return __awaiter(this, void 0, void 0, function* () {
            try {
                const venueData = req.body;
                const newVenue = yield this.venueService.createVenue(venueData);
                res.status(201).json(newVenue);
            }
            catch (error) {
                res.status(500).json({ message: 'Error creating venue', error });
            }
        });
    }
    updateVenue(req, res) {
        return __awaiter(this, void 0, void 0, function* () {
            try {
                const venueId = req.params.id;
                const updatedData = req.body;
                const updatedVenue = yield this.venueService.updateVenue(venueId, updatedData);
                res.status(200).json(updatedVenue);
            }
            catch (error) {
                res.status(500).json({ message: 'Error updating venue', error });
            }
        });
    }
    uploadImages(req, res) {
        return __awaiter(this, void 0, void 0, function* () {
            try {
                const venueId = req.params.id;
                const images = req.files; // Assuming files are uploaded using a middleware like multer
                const updatedVenue = yield this.venueService.uploadImages(venueId, images);
                res.status(200).json(updatedVenue);
            }
            catch (error) {
                res.status(500).json({ message: 'Error uploading images', error });
            }
        });
    }
    manageAmenities(req, res) {
        return __awaiter(this, void 0, void 0, function* () {
            try {
                const venueId = req.params.id;
                const amenities = req.body.amenities;
                const updatedVenue = yield this.venueService.manageAmenities(venueId, amenities);
                res.status(200).json(updatedVenue);
            }
            catch (error) {
                res.status(500).json({ message: 'Error managing amenities', error });
            }
        });
    }
    setPricing(req, res) {
        return __awaiter(this, void 0, void 0, function* () {
            try {
                const venueId = req.params.id;
                const pricing = req.body.pricing;
                const updatedVenue = yield this.venueService.setPricing(venueId, pricing);
                res.status(200).json(updatedVenue);
            }
            catch (error) {
                res.status(500).json({ message: 'Error setting pricing', error });
            }
        });
    }
}
exports.VenueController = VenueController;
