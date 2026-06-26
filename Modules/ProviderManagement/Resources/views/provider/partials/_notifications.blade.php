@foreach($notifications as $notification)
    <a href="#" class="dropdown-item-text media gap-3">
        <div class="avatar title-color hover-color-c2">
            <span class="material-icons">notifications</span>
        </div>
        <div class="media-body ">
            <img src="{{$notification->cover_image_full_path}}"
                 class="avatar rounded-circle" alt="{{translate('image')}}">
            <h5 class="card-title">{{$notification->title}}</h5>
            <p class="card-text fz-14 mb-2">{{$notification->description}}</p>
            <span class="card-text fz-12 text-opacity-75">{{ format_relative_time_ago($notification->created_at) }}</span>
        </div>
    </a>
    <div class="dropdown-divider"></div>
@endforeach
