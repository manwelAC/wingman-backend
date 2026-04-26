# Fingerprint Authentication

Biometric login using the user's phone's local fingerprint scanner. The fingerprint data **never leaves the device** — the phone's OS handles authentication locally.

## How It Works

1. User enters username/email on login screen
2. App offers: "Use fingerprint to login?"
3. Phone prompts for fingerprint scan (same as lock screen)
4. Phone's secure hardware verifies internally
5. App sends only `email_or_username` to backend
6. Backend returns login token (no password needed)

## Security

✅ **Fingerprint never sent over network**  
✅ **Handled by phone's TEE (Trusted Execution Environment)**  
✅ **Same security as lock screen**  
✅ **Can't be intercepted or replayed**  
✅ **Backend trusts the phone did verification**

---

## Backend Endpoints

### 1. Enroll Fingerprint
```
POST /api/auth/enroll-fingerprint
Authorization: Bearer {token}
```
**Response (200):**
```json
{
  "message": "Fingerprint enrolled successfully.",
  "fingerprint_enrolled": true
}
```
Call after user logs in to enable fingerprint.

---

### 2. Login with Fingerprint
```
POST /api/auth/login-fingerprint
Content-Type: application/json

{
  "email_or_username": "john_doe"
}
```

**Response (200) - Verified:**
```json
{
  "token": "abc123xyz",
  "user": {
    "id": 1,
    "username": "john_doe",
    "email": "john@example.com",
    "display_name": "John",
    "user_type": "pilot",
    "games_expertise": ["CODM", "MLBB"],
    "is_verified": false,
    "profile_image_url": null
  }
}
```

**Response (200) - Unverified Email:**
```json
{
  "message": "Email not verified. Please verify your email.",
  "email": "john@example.com",
  "unverified": true,
  "user": { ... }
}
```

**Response (422) - Not Enrolled:**
```json
{
  "message": "Fingerprint not enrolled for this account."
}
```

**Response (429) - Rate Limited:**
```json
{
  "message": "Too many login attempts. Please try again later.",
  "retry_after": 60
}
```

---

### 3. Check Fingerprint Status
```
GET /api/auth/me
Authorization: Bearer {token}
```

**Response includes:**
```json
{
  "fingerprint_enrolled": true,
  ...
}
```

---

### 4. Disable Fingerprint
```
POST /api/auth/disable-fingerprint
Authorization: Bearer {token}
```

**Response (200):**
```json
{
  "message": "Fingerprint disabled.",
  "fingerprint_enrolled": false
}
```

---

## Frontend Implementation

### Libraries
```bash
# Already in Expo
expo-local-authentication
```

### Login Flow
```javascript
import * as LocalAuthentication from 'expo-local-authentication';

const handleFingerprintLogin = async (emailOrUsername) => {
  try {
    // Check if device has biometric capability
    const compatible = await LocalAuthentication.hasHardwareAsync();
    if (!compatible) {
      alert('Device does not support biometrics');
      return;
    }

    // Prompt for fingerprint
    const result = await LocalAuthentication.authenticateAsync({
      disableDeviceFallback: false,
      reason: 'Authenticate to login to Wingman',
    });

    if (result.success) {
      // Send to backend
      const response = await fetch('https://api.wingman.com/api/auth/login-fingerprint', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          email_or_username: emailOrUsername,
        }),
      });

      const data = await response.json();

      if (response.ok) {
        // Store token
        await SecureStore.setItemAsync('authToken', data.token);

        if (data.unverified) {
          // Redirect to verify email
          navigation.navigate('VerifyEmail', { email: data.email });
        } else {
          // Redirect to home
          navigation.navigate('Home');
        }
      } else {
        alert(data.message);
      }
    } else {
      // User cancelled or failed
      alert('Fingerprint authentication failed');
    }
  } catch (error) {
    alert('Error: ' + error.message);
  }
};
```

### Enrollment Flow
```javascript
const handleEnrollFingerprint = async (token) => {
  try {
    const response = await fetch('https://api.wingman.com/api/auth/enroll-fingerprint', {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${token}`,
      },
    });

    const data = await response.json();

    if (response.ok) {
      alert('Fingerprint enrolled successfully!');
      // Update UI to show fingerprint is enabled
    } else {
      alert(data.message);
    }
  } catch (error) {
    alert('Error: ' + error.message);
  }
};
```

### Settings/Profile Screen
```javascript
// Show fingerprint toggle
const FingerprintSettings = ({ token, fingerprintEnrolled }) => {
  const [isEnabled, setIsEnabled] = useState(fingerprintEnrolled);

  const toggleFingerprint = async () => {
    const endpoint = isEnabled 
      ? 'disable-fingerprint' 
      : 'enroll-fingerprint';

    const response = await fetch(
      `https://api.wingman.com/api/auth/${endpoint}`,
      {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${token}`,
        },
      }
    );

    if (response.ok) {
      setIsEnabled(!isEnabled);
    }
  };

  return (
    <Switch
      value={isEnabled}
      onValueChange={toggleFingerprint}
      label="Use fingerprint to login"
    />
  );
};
```

---

## Database Schema

Added to `users` table:
```sql
ALTER TABLE users ADD COLUMN fingerprint_enrolled BOOLEAN DEFAULT false;
```

Migration: `2026_04_26_000000_add_fingerprint_to_users_table.php`

---

## Login Screen UI

```
┌─────────────────────────┐
│   Wingman Login         │
├─────────────────────────┤
│                         │
│  [Email or Username ] │
│                         │
│  [Password        ]   │
│                         │
│  [ Login Button  ]    │
│                         │
│  ─────────────────    │
│  or use fingerprint   │
│  [ 👆 Fingerprint]   │
│                         │
└─────────────────────────┘
```

---

## User Journey

**First Login (Enrollment Prompt):**
1. User logs in with email + password
2. App calls `GET /api/auth/me` to check status
3. If `fingerprint_enrolled: false`, show modal: "Enable fingerprint for faster login?"
4. User taps "Enable" (or can skip)
5. POST `/api/auth/enroll-fingerprint`
6. Fingerprint is now enrolled

**Subsequent Logins (with fingerprint):**
1. User enters email/username
2. Sees "Use fingerprint?" option
3. Taps fingerprint button
4. Phone prompts for scan
5. POST `/api/auth/login-fingerprint`
6. Receives token, logged in

**Profile/Settings Management:**
- User can enable/disable fingerprint anytime
- Located in: Settings → Security → "Use Fingerprint Login"
- Toggle on/off → POST `/api/auth/enroll-fingerprint` or `/api/auth/disable-fingerprint`

---

## Profile/Settings Screen Location

Add fingerprint toggle in user's security settings:

```
App Navigation
└── Profile/Settings Tab
    └── Security Settings
        ├── Change Password
        ├── 🔐 Use Fingerprint Login  [Toggle Switch]
        └── Manage Devices
```

**Code example** already shown above in "Settings/Profile Screen" section.

---

## Rate Limiting

- **Fingerprint login**: 5 attempts per 60 seconds per email/username per IP
- Same as regular login to prevent brute force

---

## Notes

- User can disable fingerprint anytime in settings
- If user changes phone, fingerprint still works (phone-agnostic)
- Backend doesn't store fingerprint data — only tracks enrollment status
- Fingerprint is optional; regular login still available