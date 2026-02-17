"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
const express_1 = require("express");
const venueController_1 = require("../controllers/venueController");
const router = (0, express_1.Router)();
const venueController = new venueController_1.VenueController();
// Route to create a new venue
router.post('/', venueController.createVenue);
// Route to update venue details
router.put('/:id', venueController.updateVenue);
// Route to upload images
router.post('/:id/images', venueController.uploadImages);
// Route to manage amenities
router.put('/:id/amenities', venueController.manageAmenities);
// Route to set pricing
router.put('/:id/pricing', venueController.setPricing);
exports.default = router;
