/**
 * Pure booking calculations and validation logic
 * Mirrored in PHP functions (includes/functions.php) for backend enforcement
 */

export function calculateTotalAmount(pricePerPerson: number, travelers: number): number {
  if (pricePerPerson < 0 || travelers < 1) {
    return 0;
  }
  return Math.round(pricePerPerson * travelers * 100) / 100;
}

export function generateBookingReference(
  dateObj: Date = new Date(),
  randomSuffix: string = Math.random().toString(36).substring(2, 7).toUpperCase()
): string {
  const year = dateObj.getFullYear();
  return `BK-${year}-${randomSuffix}`;
}

export function isValidTravelDate(dateStr: string, minDate: Date = new Date()): boolean {
  if (!dateStr || !/^\d{4}-\d{2}-\d{2}$/.test(dateStr)) {
    return false;
  }
  const travelDate = new Date(`${dateStr}T00:00:00`);
  if (isNaN(travelDate.getTime())) {
    return false;
  }
  // Reset minDate to start of day
  const normalizedMin = new Date(minDate.getFullYear(), minDate.getMonth(), minDate.getDate());
  return travelDate >= normalizedMin;
}

export function sanitizeInput(input: string): string {
  if (!input) return '';
  return input
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;')
    .trim();
}

export function validateBookingForm(data: {
  travelDate: string;
  travelers: number;
  packagePrice: number;
}): { valid: boolean; errors: string[] } {
  const errors: string[] = [];

  if (!data.travelDate) {
    errors.push('Travel date is required.');
  } else if (!isValidTravelDate(data.travelDate)) {
    errors.push('Travel date must be today or in the future.');
  }

  if (!data.travelers || data.travelers < 1) {
    errors.push('Number of travelers must be at least 1.');
  } else if (data.travelers > 30) {
    errors.push('Maximum group size for online booking is 30 people.');
  }

  if (data.packagePrice <= 0) {
    errors.push('Invalid package price.');
  }

  return {
    valid: errors.length === 0,
    errors,
  };
}

export function formatCurrencyINR(amount: number): string {
  return new Intl.NumberFormat('en-IN', {
    style: 'currency',
    currency: 'INR',
    maximumFractionDigits: 0,
  }).format(amount);
}

export function calculateInvoiceGst(totalAmount: number): {
  taxableValue: number;
  cgst: number;
  sgst: number;
  total: number;
} {
  const taxable = Math.round((totalAmount / 1.05) * 100) / 100;
  const totalTax = Math.round((totalAmount - taxable) * 100) / 100;
  const cgst = Math.round((totalTax / 2) * 100) / 100;
  const sgst = Math.round((totalTax - cgst) * 100) / 100;

  return {
    taxableValue: taxable,
    cgst,
    sgst,
    total: Math.round(totalAmount * 100) / 100,
  };
}

export function generateInvoiceNumber(bookingId: number, year: number = 2026): string {
  return `INV-${year}-${String(bookingId).padStart(5, '0')}`;
}

