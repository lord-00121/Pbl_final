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
exports.UserController = void 0;
const userService_1 = require("../services/userService");
class UserController {
    constructor() {
        this.userService = new userService_1.UserService();
    }
    getProfile(req, res) {
        return __awaiter(this, void 0, void 0, function* () {
            try {
                const userId = req.user.id; // Assuming user ID is stored in req.user
                const userProfile = yield this.userService.getUserById(userId);
                res.status(200).json(userProfile);
            }
            catch (error) {
                res.status(500).json({ message: 'Error retrieving user profile', error });
            }
        });
    }
    updateUserProfile(req, res) {
        return __awaiter(this, void 0, void 0, function* () {
            try {
                const userId = req.user.id;
                const updatedData = req.body;
                const updatedProfile = yield this.userService.updateUser(userId, updatedData);
                res.status(200).json(updatedProfile);
            }
            catch (error) {
                res.status(500).json({ message: 'Error updating user profile', error });
            }
        });
    }
    verifySeller(req, res) {
        return __awaiter(this, void 0, void 0, function* () {
            try {
                const userId = req.user.id;
                const verificationStatus = yield this.userService.verifySeller(userId);
                res.status(200).json({ message: 'Seller verification status updated', verificationStatus });
            }
            catch (error) {
                res.status(500).json({ message: 'Error verifying seller', error });
            }
        });
    }
}
exports.UserController = UserController;
