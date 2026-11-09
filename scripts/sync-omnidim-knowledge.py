#!/usr/bin/env python3
"""
OmniDimension Knowledge Base & Context Sync Script
TourismPortal Travel Concierge (Live Agent Riya)

Extracts all 10 destinations and 10 tour packages from the SQLite database
and updates the OmniDimension Agent via REST API with the complete knowledge context.
"""

import os
import json
import sqlite3
import urllib.request
import urllib.error

DB_PATH = os.path.join(os.path.dirname(__file__), '..', 'database', 'tourism_portal.db')
API_KEY = os.getenv('OMNIDIM_API_KEY', 'MfgSlLBg39viJwRYpA5_w1zaqbvdGbM7m0VpAVbMr-Q')
AGENT_ID = os.getenv('OMNIDIM_AGENT_ID', '2601')
BASE_URL = 'https://omnidim.io/api/v1'

def sync_knowledge():
    print(f"Connecting to database: {DB_PATH}")
    conn = sqlite3.connect(DB_PATH)
    conn.row_factory = sqlite3.Row
    cur = conn.cursor()

    cur.execute("SELECT id, name, state, country, description, attractions, best_time_to_visit FROM destinations ORDER BY id ASC")
    destinations = [dict(r) for r in cur.fetchall()]

    cur.execute("""
        SELECT p.id, p.destination_id, p.title, p.description, p.price, p.duration, p.itinerary, p.facilities, p.availability, 
               d.name as dest_name, d.state as dest_state 
        FROM packages p 
        JOIN destinations d ON p.destination_id = d.id 
        ORDER BY p.id ASC
    """)
    packages = [dict(r) for r in cur.fetchall()]
    conn.close()

    print(f"Loaded {len(destinations)} destinations and {len(packages)} packages.")

    # 1. Identity & Contact Information
    identity_body = (
        "- Name: Riya, the official Voice Travel Concierge for TourismPortal.\n"
        "- Location: Headquartered in Bareilly, Uttar Pradesh, India.\n"
        "- Support Hotline: 999-787-9985 (available 24/7 for booking assistance and support).\n"
        "- Official Emails: support@tourismportal.com and nbma142777@gmail.com.\n"
        "- Personality: Warm, hospitable, highly knowledgeable Indian travel advisor. Speaks fluent English and Hindi.\n"
        "- Mission: Help travelers discover destinations, compare itineraries, calculate package totals, and confirm bookings directly into the TourismPortal platform."
    )

    # 2. Destinations Catalog
    destinations_lines = []
    for d in destinations:
        destinations_lines.append(
            f"• {d['name']} ({d['state']}, {d['country']}):\n"
            f"  - Highlights: {d['description']}\n"
            f"  - Key Attractions: {d['attractions']}\n"
            f"  - Best Season to Visit: {d['best_time_to_visit']}"
        )
    destinations_body = "\n\n".join(destinations_lines)

    # 3. Packages Catalog
    packages_lines = []
    for p in packages:
        packages_lines.append(
            f"• Package #{p['id']}: {p['title']} ({p['dest_name']}, {p['dest_state']})\n"
            f"  - Duration: {p['duration']}\n"
            f"  - Official Price: ₹{int(p['price']):,} per person (INR)\n"
            f"  - Inclusions & Facilities: {p['facilities']}\n"
            f"  - Package Overview: {p['description']}"
        )
    packages_body = "\n\n".join(packages_lines)

    # 4. Detailed Day-by-Day Itineraries
    itineraries_lines = []
    for p in packages:
        itineraries_lines.append(
            f"=== Package #{p['id']}: {p['title']} ({p['dest_name']}, {p['duration']}) ===\n"
            f"{p['itinerary']}"
        )
    itineraries_body = "\n\n".join(itineraries_lines)

    # 5. Voice Booking Flow & Calculations
    booking_body = (
        "- Pricing Formula: Total Price = Package Price Per Person × Number of Travelers.\n"
        "  * Examples: 2 travelers for Manali (₹14,999) = ₹29,998; 2 travelers for Goa (₹11,499) = ₹22,998; 2 travelers for Kashmir (₹26,999) = ₹53,998.\n"
        "- Voice Booking Flow:\n"
        "  1. Identify the traveler's preferred destination or package.\n"
        "  2. Collect the traveler's Full Name.\n"
        "  3. Collect their 10-digit Mobile Phone Number (repeat back to confirm accuracy).\n"
        "  4. Collect Preferred Travel Date.\n"
        "  5. Collect Number of Travelers.\n"
        "  6. State the total cost and clearly ask for confirmation.\n"
        "- Post-Booking Assurance: Inform the caller that their reservation is logged in TourismPortal and is visible under 'Booked Tours' on the Tour Packages page.\n"
        "- Payment Policy: Zero advance payment required on the call. Travelers can pay safely upon pickup or hotel check-in. Free cancellation up to 48 hours before the trip."
    )

    # 6. FAQ & Policies
    faq_body = (
        "- Physical Headquarters: Bareilly, Uttar Pradesh, India.\n"
        "- Support Phone: 999-787-9985 | Emails: support@tourismportal.com & nbma142777@gmail.com.\n"
        "- Transport: All packages include private, sanitized AC vehicles (Sedan or SUV depending on group size) with professional chauffeurs.\n"
        "- Meals: Daily breakfast included across all 10 packages. Manali, Kashmir, Darjeeling, and Udaipur include daily dinners. Rishikesh includes all 3 meals at the riverside camp.\n"
        "- Airport / Station Transfers: Included for all destinations.\n"
        "- Sightseeing Permits: Permits for Solang Valley, Gulmarg Gondola Phase 1 & 2, Eravikulam National Park, and boat tickets for Lake Pichola, Ganges, and Mandovi cruises are included in respective packages."
    )

    context_breakdown = [
        {"title": "Identity & TourismPortal Contacts", "body": identity_body, "is_enabled": True},
        {"title": "Complete Destinations Catalog (India)", "body": destinations_body, "is_enabled": True},
        {"title": "Complete Tour Packages & Pricing", "body": packages_body, "is_enabled": True},
        {"title": "Detailed Day-by-Day Itineraries", "body": itineraries_body, "is_enabled": True},
        {"title": "Voice Booking Flow & Calculations", "body": booking_body, "is_enabled": True},
        {"title": "Travel Policies & Frequently Asked Questions", "body": faq_body, "is_enabled": True}
    ]

    payload = {
        "name": "TourismPortal Travel Concierge",
        "welcome_message": "Namaste and hello! I am Riya, your official voice travel concierge from TourismPortal in Bareilly. Which incredible destination in India would you like to explore today?",
        "context_breakdown": context_breakdown
    }

    url = f"{BASE_URL}/agents/{AGENT_ID}"
    print(f"Sending PUT request to {url} with {len(context_breakdown)} context sections...")

    req = urllib.request.Request(
        url,
        data=json.dumps(payload).encode('utf-8'),
        headers={
            "Authorization": f"Bearer {API_KEY}",
            "Content-Type": "application/json"
        },
        method="PUT"
    )

    try:
        with urllib.request.urlopen(req) as resp:
            data = json.loads(resp.read().decode('utf-8'))
            print("Successfully updated OmniDimension Agent!")
            print(f"Agent ID: {data.get('id')}")
            print(f"Agent Name: {data.get('name')}")
            print(f"Build Status: {data.get('status')}")
            return True
    except urllib.error.HTTPError as e:
        print(f"HTTP Error {e.code}: {e.read().decode('utf-8')}")
        return False
    except Exception as e:
        print(f"Error updating agent: {e}")
        return False

if __name__ == '__main__':
    sync_knowledge()
