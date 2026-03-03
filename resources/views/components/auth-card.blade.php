<div class="login-page min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-gray-100">
    <div>
        {{ $logo }}
    </div>

    <div class="w-full sm:max-w-md mt-6 px-6 py-4 bg-white shadow-md overflow-hidden sm:rounded-lg">
        {{ $slot }}
    </div>
    <div style="color:rgb(145, 145, 145); text-align:center; font-size:0.8rem; margin-top:1rem;">
        <p>&nbsp;</p>
        <p class="sm:center">
            Powered by Mbira ERP Engine {{config('version.tag')}}.
            <br> Supported by <a href="https://www.havenplustechnologies.co.mw">HavenPlus Technologies</a>
        </p>
    </div>
</div>
