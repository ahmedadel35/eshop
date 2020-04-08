<div class="row pt-5">
    <div class="col-12 col-sm-6">
        <h4 class="text-primary">${{number_format($p->savedPrice, 2)}}</h4>
        @if ($p->save)
        <h5><del class="text-muted">${{\number_format($p->price, 2)}}</del>
        </h5>
        @endif
        <p>
            <strong>@lang('t.show.color'):</strong>
            <span class="p-1 px-2 border border-dark">{{$p->color[0]}}</span>
        </p>
        <p>
            <strong class="d-block">@lang('t.show.desc')</strong>
            <span>{{$p->info}}</span>
        </p>
    </div>
    <div class="col-12 col-sm-6">
        <div class="d-block">
            <div class="row">
                <div class="col-4">
                    <select class="custom-select" name="cartAmount"
                        v-model="h.d.cartAmount">
                        @foreach (range(1, $p->amount) as $i)
                        <option :value='{{$i}}'>{{$i}}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-8">
                    <button class="btn btn-primary btn-block mb-2"
                        v-on:click="h.d.addToCart('{{$p->toJson()}}', h.d.cartAmount)">
                        <x-btn-loader :id="$p->id.'spinnerLoader'">
                        </x-btn-loader>
                        @lang('t.addCart')
                    </button>
                </div>
            </div>
            <strong class="text-danger">{{$p->amount}}
                @lang('t.show.stock')</strong>
        </div>
        <hr />
        <div class="d-block">
            <p>
                <strong>@lang('t.show.cond'):</strong>
                <span>
                    {{$p->is_used ? __('t.show.used') : __('t.show.new')}}
                </span>
            </p>
            <p>
                <strong>@lang('t.show.soldBy'):</strong>
                <span>
                    {{$p->user->name}}
                </span>
            </p>
        </div>
    </div>
</div>