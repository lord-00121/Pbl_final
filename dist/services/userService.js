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
var __importDefault = (this && this.__importDefault) || function (mod) {
    return (mod && mod.__esModule) ? mod : { "default": mod };
};
Object.defineProperty(exports, "__esModule", { value: true });
exports.UserService = void 0;
const user_1 = require("../models/user");
const bcryptjs_1 = __importDefault(require("bcryptjs"));
const jsonwebtoken_1 = __importDefault(require("jsonwebtoken"));
class UserService {
    constructor() {
        this.userModel = user_1.User;
    }
    createUser(email, password, userType, profileDetails) {
        return __awaiter(this, void 0, void 0, function* () {
            const hashedPassword = yield bcryptjs_1.default.hash(password, 10);
            const newUser = new this.userModel({
                email,
                password: hashedPassword,
                userType,
                profileDetails,
            });
            return yield newUser.save();
        });
    }
    loginUser(email, password, isAdmin = false) {
        return __awaiter(this, void 0, void 0, function* () {
            const user = yield this.userModel.findOne({ email });
            if (!user) {
                throw new Error('User not found');
            }
            if (isAdmin && user.userType !== 'seller') {
                throw new Error('Admin access denied');
            }
            const isMatch = yield bcryptjs_1.default.compare(password, user.password);
            if (!isMatch) {
                throw new Error('Invalid credentials');
            }
            const token = jsonwebtoken_1.default.sign({ id: user._id, userType: user.userType }, process.env.JWT_SECRET || 'secret', { expiresIn: '1h' });
            return { token, user };
        });
    }
    getUserById(userId) {
        return __awaiter(this, void 0, void 0, function* () {
            return yield this.userModel.findById(userId);
        });
    }
    updateUser(userId, updateData) {
        return __awaiter(this, void 0, void 0, function* () {
            return yield this.userModel.findByIdAndUpdate(userId, updateData, { new: true });
        });
    }
    deleteUser(userId) {
        return __awaiter(this, void 0, void 0, function* () {
            return yield this.userModel.findByIdAndDelete(userId);
        });
    }
    verifySeller(userId) {
        return __awaiter(this, void 0, void 0, function* () {
            // This would typically involve additional verification logic
            return yield this.userModel.findByIdAndUpdate(userId, { verified: true }, { new: true });
        });
    }
}
exports.UserService = UserService;
