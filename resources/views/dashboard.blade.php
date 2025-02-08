@extends('base')
@section('content')
<div class="pagetitle">
    <h1>Result Page</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.html">Home</a></li>
            <li class="breadcrumb-item">Pages</li>
            <li class="breadcrumb-item active">Result</li>
        </ol>
    </nav>
</div><!-- End Page Title -->

<section class="section">
    <div class="row">
        <div class="col-lg-6">

            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Update Result</h5>
                    <form method="post" action="{{ route('result.save') }}">
                        @csrf
                        <div class="row mb-1">
                            <label for="inputEmail3" class="col-sm-2 col-form-label">Play Date</label>
                            <div class="col-sm-10">
                                <input type="date" class="form-control" name="play_date" value="{{ date('Y-m-d') }}">
                            </div>
                            @error('play_date')
                            <small class="text-danger">{{ $errors->first('play_date') }}</small>
                            @enderror
                        </div>
                        <div class="row mb-1">
                            <label for="inputEmail3" class="col-sm-2 col-form-label">Play</label>
                            <div class="col-sm-10">
                                <select class="form-control" name="play_id">
                                    <option value="">Select Play</option>
                                    @forelse($plays as $key => $play)
                                    <option value="{{ $play->id }}">{{ $play->name }}</option>
                                    @empty
                                    @endforelse
                                </select>
                            </div>
                            @error('play_id')
                            <small class="text-danger">{{ $errors->first('play_id') }}</small>
                            @enderror
                        </div>
                        <div class="row mb-1">
                            <label for="inputPassword3" class="col-sm-2 col-form-label">P1</label>
                            <div class="col-sm-10">
                                <input type="number" class="form-control num" value="{{ old('p1') }}" name="p1" max="999" placeholder="xxx">
                            </div>
                        </div>
                        <div class="row mb-1">
                            <label for="inputPassword3" class="col-sm-2 col-form-label">P2</label>
                            <div class="col-sm-10">
                                <input type="number" class="form-control num" value="{{ old('p2') }}" name="p2" max="999" placeholder="xxx">
                            </div>
                        </div>
                        <div class="row mb-1">
                            <label for="inputPassword3" class="col-sm-2 col-form-label">P3</label>
                            <div class="col-sm-10">
                                <input type="number" class="form-control num" value="{{ old('p3') }}" name="p3" max="999" placeholder="xxx">
                            </div>
                        </div>
                        <div class="row mb-1">
                            <label for="inputPassword3" class="col-sm-2 col-form-label">P4</label>
                            <div class="col-sm-10">
                                <input type="number" class="form-control num" value="{{ old('p4') }}" name="p4" max="999" placeholder="xxx">
                            </div>
                        </div>
                        <div class="row mb-1">
                            <label for="inputPassword3" class="col-sm-2 col-form-label">P5</label>
                            <div class="col-sm-10">
                                <input type="number" class="form-control num" value="{{ old('p5') }}" name="p5" max="999" placeholder="xxx">
                            </div>
                        </div>
                        <div class="row mb-1">
                            <label for="inputPassword3" class="col-sm-2 col-form-label">P6</label>
                            <div class="col-sm-10">
                                <input type="number" class="form-control num" value="{{ old('p6') }}" name="p6" max="999" placeholder="xxx">
                            </div>
                        </div>
                        <div class="row mb-1">
                            <label for="inputPassword3" class="col-sm-2 col-form-label">P7</label>
                            <div class="col-sm-10">
                                <input type="number" class="form-control num" value="{{ old('p7') }}" name="p7" max="999" placeholder="xxx">
                            </div>
                        </div>
                        <div class="row mb-1">
                            <label for="inputPassword3" class="col-sm-2 col-form-label">P8</label>
                            <div class="col-sm-10">
                                <input type="number" class="form-control num" value="{{ old('p8') }}" name="p8" max="999" placeholder="xxx">
                            </div>
                        </div>
                        <div class="row mb-1">
                            <label for="inputPassword3" class="col-sm-2 col-form-label">P9</label>
                            <div class="col-sm-10">
                                <input type="number" class="form-control num" value="{{ old('p9') }}" name="p9" max="999" placeholder="xxx">
                            </div>
                        </div>
                        <div class="row mb-1">
                            <label for="inputPassword3" class="col-sm-2 col-form-label">P10</label>
                            <div class="col-sm-10">
                                <input type="number" class="form-control num" value="{{ old('p10') }}" name="p10" max="999" placeholder="xxx">
                            </div>
                        </div>
                        <div class="row mb-1">
                            <label for="inputPassword3" class="col-sm-2 col-form-label">P11</label>
                            <div class="col-sm-10">
                                <input type="number" class="form-control num" value="{{ old('p11') }}" name="p11" max="999" placeholder="xxx">
                            </div>
                        </div>
                        <div class="row mb-1">
                            <label for="inputPassword3" class="col-sm-2 col-form-label">P12</label>
                            <div class="col-sm-10">
                                <input type="number" class="form-control num" value="{{ old('p12') }}" name="p12" max="999" placeholder="xxx">
                            </div>
                        </div>
                        <div class="row mb-1">
                            <label for="inputPassword3" class="col-sm-2 col-form-label">P13</label>
                            <div class="col-sm-10">
                                <input type="number" class="form-control num" value="{{ old('p13') }}" name="p13" max="999" placeholder="xxx">
                            </div>
                        </div>
                        <div class="row mb-1">
                            <label for="inputPassword3" class="col-sm-2 col-form-label">P14</label>
                            <div class="col-sm-10">
                                <input type="number" class="form-control num" value="{{ old('p14') }}" name="p14" max="999" placeholder="xxx">
                            </div>
                        </div>
                        <div class="row mb-1">
                            <label for="inputPassword3" class="col-sm-2 col-form-label">P15</label>
                            <div class="col-sm-10">
                                <input type="number" class="form-control num" value="{{ old('p15') }}" name="p15" max="999" placeholder="xxx">
                            </div>
                        </div>
                        <div class="row mb-1">
                            <label for="inputPassword3" class="col-sm-2 col-form-label">P16</label>
                            <div class="col-sm-10">
                                <input type="number" class="form-control num" value="{{ old('p16') }}" name="p16" max="999" placeholder="xxx">
                            </div>
                        </div>
                        <div class="row mb-1">
                            <label for="inputPassword3" class="col-sm-2 col-form-label">P17</label>
                            <div class="col-sm-10">
                                <input type="number" class="form-control num" value="{{ old('p17') }}" name="p17" max="999" placeholder="xxx">
                            </div>
                        </div>
                        <div class="row mb-1">
                            <label for="inputPassword3" class="col-sm-2 col-form-label">P18</label>
                            <div class="col-sm-10">
                                <input type="number" class="form-control num" value="{{ old('p18') }}" name="p18" max="999" placeholder="xxx">
                            </div>
                        </div>
                        <div class="row mb-1">
                            <label for="inputPassword3" class="col-sm-2 col-form-label">P19</label>
                            <div class="col-sm-10">
                                <input type="number" class="form-control num" value="{{ old('p19') }}" name="p19" max="999" placeholder="xxx">
                            </div>
                        </div>
                        <div class="row mb-1">
                            <label for="inputPassword3" class="col-sm-2 col-form-label">P20</label>
                            <div class="col-sm-10">
                                <input type="number" class="form-control num" value="{{ old('p20') }}" name="p20" max="999" placeholder="xxx">
                            </div>
                        </div>
                        <div class="row mb-1">
                            <label for="inputPassword3" class="col-sm-2 col-form-label">P21</label>
                            <div class="col-sm-10">
                                <input type="number" class="form-control num" value="{{ old('p21') }}" name="p21" max="999" placeholder="xxx">
                            </div>
                        </div>
                        <div class="row mb-1">
                            <label for="inputPassword3" class="col-sm-2 col-form-label">P22</label>
                            <div class="col-sm-10">
                                <input type="number" class="form-control num" value="{{ old('p22') }}" name="p22" max="999" placeholder="xxx">
                            </div>
                        </div>
                        <div class="row mb-1">
                            <label for="inputPassword3" class="col-sm-2 col-form-label">P23</label>
                            <div class="col-sm-10">
                                <input type="number" class="form-control num" value="{{ old('p23') }}" name="p23" max="999" placeholder="xxx">
                            </div>
                        </div>
                        <div class="row mb-1">
                            <label for="inputPassword3" class="col-sm-2 col-form-label">P24</label>
                            <div class="col-sm-10">
                                <input type="number" class="form-control num" value="{{ old('p24') }}" name="p24" max="999" placeholder="xxx">
                            </div>
                        </div>
                        <div class="row mb-1">
                            <label for="inputPassword3" class="col-sm-2 col-form-label">P25</label>
                            <div class="col-sm-10">
                                <input type="number" class="form-control num" value="{{ old('p25') }}" name="p25" max="999" placeholder="xxx">
                            </div>
                        </div>
                        <div class="row mb-1">
                            <label for="inputPassword3" class="col-sm-2 col-form-label">P26</label>
                            <div class="col-sm-10">
                                <input type="number" class="form-control num" value="{{ old('p26') }}" name="p26" max="999" placeholder="xxx">
                            </div>
                        </div>
                        <div class="row mb-1">
                            <label for="inputPassword3" class="col-sm-2 col-form-label">P27</label>
                            <div class="col-sm-10">
                                <input type="number" class="form-control num" value="{{ old('p27') }}" name="p27" max="999" placeholder="xxx">
                            </div>
                        </div>
                        <div class="row mb-1">
                            <label for="inputPassword3" class="col-sm-2 col-form-label">P28</label>
                            <div class="col-sm-10">
                                <input type="number" class="form-control num" value="{{ old('p28') }}" name="p28" max="999" placeholder="xxx">
                            </div>
                        </div>
                        <div class="row mb-1">
                            <label for="inputPassword3" class="col-sm-2 col-form-label">P29</label>
                            <div class="col-sm-10">
                                <input type="number" class="form-control num" value="{{ old('p29') }}" name="p29" max="999" placeholder="xxx">
                            </div>
                        </div>
                        <div class="row mb-1">
                            <label for="inputPassword3" class="col-sm-2 col-form-label">P30</label>
                            <div class="col-sm-10">
                                <input type="number" class="form-control num" value="{{ old('p30') }}" name="p30" max="999" placeholder="xxx">
                            </div>
                        </div>
                        <div class="row mb-1">
                            <label for="inputPassword3" class="col-sm-2 col-form-label">P31</label>
                            <div class="col-sm-10">
                                <input type="number" class="form-control num" value="{{ old('p31') }}" name="p31" max="999" placeholder="xxx">
                            </div>
                        </div>
                        <div class="row mb-1">
                            <label for="inputPassword3" class="col-sm-2 col-form-label">P32</label>
                            <div class="col-sm-10">
                                <input type="number" class="form-control num" value="{{ old('p32') }}" name="p32" max="999" placeholder="xxx">
                            </div>
                        </div>
                        <div class="row mb-1">
                            <label for="inputPassword3" class="col-sm-2 col-form-label">P33</label>
                            <div class="col-sm-10">
                                <input type="number" class="form-control num" value="{{ old('p33') }}" name="p33" max="999" placeholder="xxx">
                            </div>
                        </div>
                        <div class="row mb-1">
                            <label for="inputPassword3" class="col-sm-2 col-form-label">P34</label>
                            <div class="col-sm-10">
                                <input type="number" class="form-control num" value="{{ old('p34') }}" name="p34" max="999" placeholder="xxx">
                            </div>
                        </div>
                        <div class="row mb-1">
                            <label for="inputPassword3" class="col-sm-2 col-form-label">P35</label>
                            <div class="col-sm-10">
                                <input type="number" class="form-control num" value="{{ old('p35') }}" name="p35" max="999" placeholder="xxx">
                            </div>
                        </div>
                        <div class="text-center mt-3">
                            <button type="submit" class="btn btn-primary btn-submit">Submit</button>
                            <button type="reset" class="btn btn-secondary">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Result</h5>
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>SL No.</th>
                                    <th>Play</th>
                                    <th>Date</th>
                                    <th>Result</th>
                                    <th>Edit</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($results as $key => $result)
                                <td>{{ $key + 1 }}</td>
                                <td>{{ $result?->plays?->name }}</td>
                                <td>{{ $result->play_date->format('d-M-Y') }}</td>
                                <td>
                                    1: {{ $result->p1 }}<br />
                                    2: {{ $result->p2 }}<br />
                                    3: {{ $result->p3 }}<br />
                                    4: {{ $result->p4 }}<br />
                                    5: {{ $result->p5 }}<br />
                                    {{ $result->p6 }} | {{ $result->p7 }} | {{ $result->p8 }} | {{ $result->p9 }} | {{ $result->p10 }} | {{ $result->p11 }} | {{ $result->p12 }} | {{ $result->p13 }} | {{ $result->p14 }} | {{ $result->p15 }} | {{ $result->p16 }} | {{ $result->p17 }} | {{ $result->p18 }} | {{ $result->p19 }} | {{ $result->p20 }} | {{ $result->p21 }} | {{ $result->p22 }} | {{ $result->p23 }} | {{ $result->p24 }} | {{ $result->p25 }} | {{ $result->p26 }} | {{ $result->p27 }} | {{ $result->p28 }} | {{ $result->p29 }} | {{ $result->p30 }} | {{ $result->p31 }} | {{ $result->p32 }} | {{ $result->p33 }} | {{ $result->p34 }} | {{ $result->p35 }}
                                </td>
                                <td><a href="{{ route('result.edit', $result->id) }}">Edit</a></td>
                                @empty
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>
</section>
@endsection('content')