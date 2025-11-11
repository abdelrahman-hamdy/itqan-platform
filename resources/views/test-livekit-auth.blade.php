@extends('components.layouts.app')

@section('content')
<div class="container mx-auto p-4">
    <h1 class="text-2xl font-bold mb-4">LiveKit Authentication Test</h1>
    
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-lg font-semibold mb-2">Current User Info:</h2>
        @auth
            <p>ID: {{ auth()->user()->id }}</p>
            <p>Name: {{ auth()->user()->name }}</p>
            <p>Email: {{ auth()->user()->email }}</p>
            <p>Type: {{ auth()->user()->user_type }}</p>
            <p>Can Control: {{ in_array(auth()->user()->user_type, ['quran_teacher', 'academic_teacher', 'admin', 'super_admin']) ? 'YES' : 'NO' }}</p>
        @else
            <p>Not authenticated</p>
        @endauth
    </div>
    
    <div class="bg-white rounded-lg shadow p-6 mt-4">
        <h2 class="text-lg font-semibold mb-2">Test Mute API:</h2>
        <button id="test-mute" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
            Test Mute All Students
        </button>
        <div id="test-result" class="mt-4 p-4 rounded" style="display: none;"></div>
    </div>
</div>

<script>
document.getElementById('test-mute').addEventListener('click', async () => {
    const resultDiv = document.getElementById('test-result');
    resultDiv.style.display = 'block';
    resultDiv.className = 'mt-4 p-4 rounded bg-gray-100';
    resultDiv.innerHTML = 'Testing...';
    
    try {
        // Log request details
        console.log('Making request to: /livekit/participants/mute-all-students');
        console.log('CSRF Token:', document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'));
        console.log('Session Cookie:', document.cookie);
        
        const response = await fetch('/livekit/participants/mute-all-students', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin',
            body: JSON.stringify({
                room_name: 'test-room',
                muted: true
            })
        });
        
        const responseText = await response.text();
        let responseData;
        try {
            responseData = JSON.parse(responseText);
        } catch (e) {
            responseData = { raw: responseText };
        }
        
        console.log('Response Status:', response.status);
        console.log('Response Headers:', response.headers);
        console.log('Response Data:', responseData);
        
        if (response.ok) {
            resultDiv.className = 'mt-4 p-4 rounded bg-green-100';
            resultDiv.innerHTML = `
                <strong>Success!</strong><br>
                Status: ${response.status}<br>
                Response: <pre>${JSON.stringify(responseData, null, 2)}</pre>
            `;
        } else {
            resultDiv.className = 'mt-4 p-4 rounded bg-red-100';
            resultDiv.innerHTML = `
                <strong>Error!</strong><br>
                Status: ${response.status}<br>
                Response: <pre>${JSON.stringify(responseData, null, 2)}</pre>
            `;
        }
    } catch (error) {
        console.error('Request error:', error);
        resultDiv.className = 'mt-4 p-4 rounded bg-red-100';
        resultDiv.innerHTML = `
            <strong>Request Failed!</strong><br>
            Error: ${error.message}
        `;
    }
});
</script>
@endsection
