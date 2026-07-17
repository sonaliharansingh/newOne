<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
</head>
<body style="font-family: sans-serif; color: #111827;">
    <p>Dear {{ $recipient->name }},</p>

    <p>
        Your registration for <strong>{{ $group->group_name }}</strong> has been confirmed.
        Your booking ID is <strong>{{ $group->bookingId() }}</strong>.
    </p>

    @if ($allocation && $allocation->room)
        <p>
            You have been allocated <strong>Room {{ $allocation->room->room_number }}</strong>
            at <strong>{{ $allocation->room->hotel->hotel_name }}</strong>.
        </p>
    @else
        <p>Your room details will be shared shortly.</p>
    @endif

    <p>Please keep your booking ID for reference at check-in.</p>

    <p>Thank you.</p>
</body>
</html>
