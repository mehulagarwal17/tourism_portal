import test from 'node:test';
import assert from 'node:assert/strict';
import {
  calculateTotalAmount,
  generateBookingReference,
  isValidTravelDate,
  sanitizeInput,
  validateBookingForm,
  formatCurrencyINR,
  calculateInvoiceGst,
  generateInvoiceNumber,
} from './bookingLogic';

test('calculateTotalAmount computes correct total for multiple travelers', () => {
  assert.equal(calculateTotalAmount(14999, 1), 14999);
  assert.equal(calculateTotalAmount(14999, 3), 44997);
  assert.equal(calculateTotalAmount(12500.5, 2), 25001);
  assert.equal(calculateTotalAmount(10000, 0), 0);
  assert.equal(calculateTotalAmount(-500, 2), 0);
});

test('generateBookingReference produces valid format', () => {
  const fixedDate = new Date('2026-06-15T00:00:00Z');
  const ref = generateBookingReference(fixedDate, '9X8YZ');
  assert.equal(ref, 'BK-2026-9X8YZ');
  assert.match(ref, /^BK-\d{4}-[A-Z0-9]+$/);
});

test('isValidTravelDate correctly accepts future dates and rejects past dates', () => {
  const baseDate = new Date('2026-05-10T12:00:00');
  assert.equal(isValidTravelDate('2026-05-10', baseDate), true);
  assert.equal(isValidTravelDate('2026-05-11', baseDate), true);
  assert.equal(isValidTravelDate('2026-05-09', baseDate), false);
  assert.equal(isValidTravelDate('invalid-date', baseDate), false);
  assert.equal(isValidTravelDate('', baseDate), false);
});

test('sanitizeInput escapes HTML control characters preventing XSS', () => {
  const malicious = '<script>alert("xss")</script>&foo=\'bar\'';
  const clean = sanitizeInput(malicious);
  assert.doesNotMatch(clean, /<script>/);
  assert.ok(clean.includes('&lt;script&gt;'));
  assert.ok(clean.includes('&quot;xss&quot;'));
});

test('validateBookingForm validates correctly', () => {
  const validResult = validateBookingForm({
    travelDate: '2026-12-25',
    travelers: 2,
    packagePrice: 15000,
  });
  assert.equal(validResult.valid, true);
  assert.equal(validResult.errors.length, 0);

  const invalidResult = validateBookingForm({
    travelDate: '2020-01-01',
    travelers: 0,
    packagePrice: 0,
  });
  assert.equal(invalidResult.valid, false);
  assert.ok(invalidResult.errors.length >= 3);
});

test('formatCurrencyINR formats Indian Rupees properly', () => {
  const formatted = formatCurrencyINR(14999);
  assert.ok(formatted.includes('14,999'));
});

test('calculateInvoiceGst computes correct SAC 998555 5% composite GST breakdown', () => {
  const result = calculateInvoiceGst(29998);
  assert.equal(result.total, 29998);
  assert.equal(result.taxableValue, 28569.52);
  assert.equal(result.cgst, 714.24);
  assert.equal(result.sgst, 714.24);
  assert.equal(Math.round((result.taxableValue + result.cgst + result.sgst) * 100) / 100, 29998);
});

test('generateInvoiceNumber produces standard padded serial invoice sequence', () => {
  assert.equal(generateInvoiceNumber(1, 2026), 'INV-2026-00001');
  assert.equal(generateInvoiceNumber(105, 2026), 'INV-2026-00105');
});

