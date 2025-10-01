@if ($images->count() > 0)
    <div style="display: flex; flex-wrap: wrap; justify-content: center; gap: 15px;">
        @foreach ($images as $img)
            <img src="{{ asset("storage/upload/$folder/" . $img->image) }}" alt="Bukti Transfer"
                style="
                    width: 150px; 
                    height: 150px; 
                    object-fit: contain; 
                    border-radius: 10px; 
                    box-shadow: 0 2px 6px rgba(0,0,0,0.2); 
                    border: 1px solid #ddd;
                    transition: transform 0.2s ease-in-out;
                "
                onmouseover="this.style.transform='scale(1.05)'" onmouseout="this.style.transform='scale(1)'">
        @endforeach
    </div>
@else
    <p style="color: #6b7280; text-align: center;">Tidak ada gambar.</p>
@endif
