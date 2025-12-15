<div class="stamp-container">
    <div class="stamp" style="
        position: absolute;
        z-index: 9999;
        top: 60%;
        left: 60%;
        transform: translate(-60%, -50%) rotate(-15deg);
        text-align: center;
    ">
        <!-- Date -->
        <div style="
            position: absolute;
            top: 2.5%;
            left: 45%;
            transform: translate(-50%, -50%);
            font-size: 5px;
            font-weight: bold;
            color: #2E3192;
            padding: 2px 6px;
            border-radius: 3px;
        ">
            {{ now()->format('d M Y') }}
        </div>

        <!-- Stamp Image -->
        <img
            src="{{ public_path('images/sealsandstamps/daron-driving-school-stamp.png') }}"
            alt="Stamp"
            style="width: 100px; height: auto;"
        >
    </div>
</div>
