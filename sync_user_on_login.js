/**
 * Auto-Sync Firebase Auth Users to Realtime Database
 * Place this code in your login page or authentication handler
 * This ensures all authenticated users (except Anonymous) are synced to /users node
 */

// Function to sync user to database after authentication
function syncUserToDatabase(user) {
    if (!user) return;
    
    // Skip anonymous users
    if (user.isAnonymous) {
        console.log('Skipping anonymous user sync');
        return;
    }
    
    const database = firebase.database();
    const userRef = database.ref('users/' + user.uid);
    
    // Determine provider
    let provider = 'email';
    if (user.providerData && user.providerData.length > 0) {
        const providerData = user.providerData[0];
        if (providerData.providerId === 'google.com') {
            provider = 'google';
        } else if (providerData.providerId === 'facebook.com') {
            provider = 'facebook';
        } else if (providerData.providerId === 'phone') {
            provider = 'phone';
        }
    }
    
    // Prepare user data
    const userData = {
        name: user.displayName || user.email || 'User',
        Username: user.displayName || user.email || 'User',
        email: user.email || 'N/A',
        Email: user.email || 'N/A',
        role: 'User', // Default role
        Role: 'User',
        status: 'Active',
        Status: 'Active',
        provider: provider,
        providerId: user.providerData && user.providerData[0] ? user.providerData[0].providerId : 'password',
        photoURL: user.photoURL || null,
        phoneNumber: user.phoneNumber || null,
        emailVerified: user.emailVerified || false,
        lastLogin: new Date().toLocaleString(),
        lastActive: new Date().toLocaleString(),
        createdAt: user.metadata.creationTime,
        lastSignInTime: user.metadata.lastSignInTime
    };
    
    // Check if user exists
    userRef.once('value').then((snapshot) => {
        if (snapshot.exists()) {
            // User exists - update last login and other dynamic fields
            userRef.update({
                lastLogin: userData.lastLogin,
                lastActive: userData.lastActive,
                lastSignInTime: userData.lastSignInTime,
                emailVerified: userData.emailVerified
            });
            console.log('User synced (updated):', user.email);
        } else {
            // New user - create full record
            userRef.set(userData);
            console.log('User synced (created):', user.email);
        }
    }).catch((error) => {
        console.error('Error syncing user:', error);
    });
}

// Listen for authentication state changes
firebase.auth().onAuthStateChanged((user) => {
    if (user) {
        // User is signed in
        syncUserToDatabase(user);
    }
});

// Also sync on successful login
// Add this to your login success handler:
/*
firebase.auth().signInWithEmailAndPassword(email, password)
    .then((userCredential) => {
        syncUserToDatabase(userCredential.user);
        // ... rest of your login logic
    });
*/

// For Google Sign-In:
/*
firebase.auth().signInWithPopup(googleProvider)
    .then((result) => {
        syncUserToDatabase(result.user);
        // ... rest of your logic
    });
*/

// For Facebook Sign-In:
/*
firebase.auth().signInWithPopup(facebookProvider)
    .then((result) => {
        syncUserToDatabase(result.user);
        // ... rest of your logic
    });
*/

// For Phone Authentication:
/*
confirmationResult.confirm(verificationCode)
    .then((result) => {
        syncUserToDatabase(result.user);
        // ... rest of your logic
    });
*/

console.log('✅ Auto-sync initialized - Authenticated users will be synced to database');