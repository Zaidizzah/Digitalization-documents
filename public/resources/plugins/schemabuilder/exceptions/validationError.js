/**
 * Creates a new ValidationError with a given message and optional rule.
 * @param {string} message - The error message.
 * @param {string|null} rule - The rule that caused the error (optional).
 */
class ValidationError extends Error {
    constructor(message, rule = null) {
        super(message);
        this.rule = rule;
    }
}
