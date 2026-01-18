<div class="stamp-container">
    <div class="stamp" style="
        position: absolute;
        z-index: 9999 !important;
        top: 70%;
        left: 60%;
        transform: translate(-60%, -50%) rotate(-15deg);
        text-align: center;
    ">
        <!-- Date -->
        <div style="
            position: absolute;
            top: 4.0%;
            left: 45%;
            transform: translate(-50%, -50%);
            font-size: 10px;
            font-weight: bold;
            color: #3a3d86;
            padding: 2px 6px;
            border-radius: 3px;
        ">
            {{ now()->format('d M, Y') }}
        </div>

        <!-- Stamp Image -->
        <img
            src="{{ public_path('images/sealsandstamps/daron-driving-school-stamp.png') }}"
            alt="Stamp"
            style="width: 180px; height: auto;"
        >
    </div>
</div>
