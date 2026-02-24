<div class="stamp-container" style="
        position: absolute;
        z-index: 9999 !important;
        top: 76%;
        left: 60%;
        transform: translate(-60%, -50%) rotate(-15deg);
        text-align: center;
    ">
    <div class="stamp" >
        <!-- Date -->
        <div style="
            position: absolute;
            top: 4.0%;
            left: 45%;
            transform: translate(-50%, -50%);
            font-size: 8px;
            font-weight: bold;
            color: #3a3d86;
            border-radius: 3px;
        ">
            {{ now()->format('d M, Y') }}
        </div>

        <!-- Stamp Image -->
        <img
            src="{{ public_path('images/sealsandstamps/daron-driving-school-stamp.png') }}"
            alt="Stamp"
            style="width: 150px; height: auto;"
        >
    </div>
</div>
