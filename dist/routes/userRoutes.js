"use strict";
var __importDefault = (this && this.__importDefault) || function (mod) {
    return (mod && mod.__esModule) ? mod : { "default": mod };
};
Object.defineProperty(exports, "__esModule", { value: true });
const express_1 = require("express");
const userController_1 = require("../controllers/userController");
const authMiddleware_1 = __importDefault(require("../middleware/authMiddleware"));
const router = (0, express_1.Router)();
const userController = new userController_1.UserController();
// Route for getting user profile
router.get('/profile', authMiddleware_1.default, userController.getProfile);
// Route for updating user profile
router.put('/profile', authMiddleware_1.default, userController.updateUserProfile);
// Route for verifying seller
router.post('/verify-seller', authMiddleware_1.default, userController.verifySeller);
exports.default = router;
