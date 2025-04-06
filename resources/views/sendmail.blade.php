<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tutorial Laravel: Send Email Via SMTP GMAIL @ qadrLabs.com</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
</head>

<body>
    <h2>{{ $email['title'] }}</h2>

    <p>Below we include the OTP code to verify your email.</p>
    <p>Keep this code secret from your closest people and everyone else.</p>

    <p style="font-weight: bolder">
        Your OTP Code: <strong>{{ $email['kode'] }}</strong>
    </p>

    <p>Or click the link below to verify your email:</p>

    <p style="font-weight: bolder">
        <a type="button" style="background-color: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; font-size: 16px; display: inline-block;" href="{{ url('/api/mahasiswa/verification?kode='.$email['kode']) }}" >Verifikasi</a>
    </p>

    <p>This code is only valid for 5 minutes.</p>
    <br>
    <p>Regards</p>
    <p>Team Development</p>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>

</html>
